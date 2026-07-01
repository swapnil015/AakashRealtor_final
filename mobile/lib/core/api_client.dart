import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import 'api_exception.dart';
import 'api_response.dart';
import 'config.dart';
import 'token_storage.dart';

/// Callback invoked when the API returns 401 so the app can sign the user out.
typedef UnauthorizedCallback = void Function();

/// Central HTTP client.
///
/// Responsibilities:
///  * Configure Dio with the base URL & timeouts.
///  * Attach the Sanctum bearer token to every request (interceptor).
///  * Unwrap the standard envelope into [ApiResponse] / [ApiException].
///  * Expose typed get/post/multipart helpers used by the service layer.
class ApiClient {
  ApiClient({
    required TokenStorage tokenStorage,
    Dio? dio,
    this.onUnauthorized,
  })  : _tokenStorage = tokenStorage,
        _dio = dio ?? Dio() {
    _dio.options
      ..baseUrl = AppConfig.apiBaseUrl
      ..connectTimeout = AppConfig.connectTimeout
      ..receiveTimeout = AppConfig.receiveTimeout
      ..headers.addAll({
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      })
      // We validate status manually so we can read the envelope on errors.
      ..validateStatus = (status) => status != null && status < 500;

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokenStorage.read();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ),
    );

    if (kDebugMode) {
      _dio.interceptors.add(LogInterceptor(
        requestBody: true,
        responseBody: false,
        logPrint: (o) => debugPrint(o.toString()),
      ));
    }
  }

  final Dio _dio;
  final TokenStorage _tokenStorage;
  final UnauthorizedCallback? onUnauthorized;

  // ---------------------------------------------------------------------------
  // Public helpers — each returns an unwrapped [ApiResponse].
  // ---------------------------------------------------------------------------

  /// GET request. [decoder] maps the raw `data` payload to [T].
  Future<ApiResponse<T>> getJson<T>(
    String path, {
    Map<String, dynamic>? query,
    required T Function(dynamic data) decoder,
  }) async {
    return _send<T>(
      () => _dio.get(path, queryParameters: _clean(query)),
      decoder,
    );
  }

  /// POST a JSON body.
  Future<ApiResponse<T>> postJson<T>(
    String path, {
    Object? body,
    required T Function(dynamic data) decoder,
  }) async {
    return _send<T>(() => _dio.post(path, data: body), decoder);
  }

  /// POST multipart/form-data (used for property image upload).
  Future<ApiResponse<T>> postMultipart<T>(
    String path, {
    required FormData formData,
    required T Function(dynamic data) decoder,
  }) async {
    return _send<T>(
      () => _dio.post(
        path,
        data: formData,
        options: Options(contentType: 'multipart/form-data'),
      ),
      decoder,
    );
  }

  // ---------------------------------------------------------------------------
  // Internal
  // ---------------------------------------------------------------------------

  /// Strip null query params so we don't send `?city=null`.
  Map<String, dynamic>? _clean(Map<String, dynamic>? query) {
    if (query == null) return null;
    final cleaned = <String, dynamic>{};
    query.forEach((k, v) {
      if (v != null && '$v'.isNotEmpty) cleaned[k] = v;
    });
    return cleaned;
  }

  Future<ApiResponse<T>> _send<T>(
    Future<Response<dynamic>> Function() run,
    T Function(dynamic data) decoder,
  ) async {
    try {
      final res = await run();
      final body = res.data;

      // Defensive: a non-map body means the server returned something unexpected.
      if (body is! Map<String, dynamic>) {
        throw ApiException(
          message: 'Unexpected response from server.',
          statusCode: res.statusCode,
        );
      }

      final success = body['success'] == true;
      final message = (body['message'] as String?) ?? '';

      if (!success || (res.statusCode ?? 200) >= 400) {
        final isUnauthorized = res.statusCode == 401;
        if (isUnauthorized) onUnauthorized?.call();
        throw ApiException(
          message: message.isNotEmpty ? message : 'Request failed.',
          statusCode: res.statusCode,
          errors: _parseErrors(body['errors']),
          isUnauthorized: isUnauthorized,
        );
      }

      final pagination = _parsePagination(body['meta']);
      return ApiResponse<T>(
        success: true,
        data: decoder(body['data']),
        message: message,
        pagination: pagination,
      );
    } on DioException catch (e) {
      throw _mapDioException(e);
    }
  }

  ApiException _mapDioException(DioException e) {
    // If the server responded with our envelope on an error code, surface it.
    final data = e.response?.data;
    if (data is Map<String, dynamic>) {
      final isUnauthorized = e.response?.statusCode == 401;
      if (isUnauthorized) onUnauthorized?.call();
      return ApiException(
        message: (data['message'] as String?) ?? 'Request failed.',
        statusCode: e.response?.statusCode,
        errors: _parseErrors(data['errors']),
        isUnauthorized: isUnauthorized,
      );
    }

    final isTimeout = e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.type == DioExceptionType.sendTimeout ||
        e.type == DioExceptionType.connectionError;

    return ApiException(
      message: isTimeout
          ? 'Network problem. Please check your connection and try again.'
          : 'Something went wrong. Please try again.',
      statusCode: e.response?.statusCode,
      isNetwork: isTimeout,
    );
  }

  Map<String, List<String>> _parseErrors(dynamic raw) {
    if (raw is! Map) return const {};
    final result = <String, List<String>>{};
    raw.forEach((key, value) {
      if (value is List) {
        result['$key'] = value.map((e) => '$e').toList();
      } else if (value != null) {
        result['$key'] = ['$value'];
      }
    });
    return result;
  }

  Pagination? _parsePagination(dynamic meta) {
    if (meta is Map && meta['pagination'] is Map) {
      return Pagination.fromJson(
        Map<String, dynamic>.from(meta['pagination'] as Map),
      );
    }
    return null;
  }
}
