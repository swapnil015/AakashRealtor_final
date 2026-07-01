/// Normalised exception thrown by [ApiClient] for any failed request.
///
/// Carries the API envelope's `message` and `errors` so the UI can show a
/// human-readable message and surface per-field validation errors.
class ApiException implements Exception {
  ApiException({
    required this.message,
    this.statusCode,
    this.errors = const {},
    this.isNetwork = false,
    this.isUnauthorized = false,
  });

  /// Human-readable message (from the envelope or a fallback).
  final String message;

  /// HTTP status code, if available.
  final int? statusCode;

  /// Field-level validation errors: {field: [messages]}.
  final Map<String, List<String>> errors;

  /// True when the failure was a connectivity/timeout problem.
  final bool isNetwork;

  /// True for 401 responses (token missing/expired).
  final bool isUnauthorized;

  /// First validation message for [field], if any.
  String? firstErrorFor(String field) {
    final list = errors[field];
    if (list == null || list.isEmpty) return null;
    return list.first;
  }

  @override
  String toString() => 'ApiException($statusCode): $message';
}
