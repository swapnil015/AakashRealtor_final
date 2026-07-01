/// App-wide configuration constants.
///
/// The API base URL can be overridden at build time via:
///   flutter run --dart-define=API_BASE_URL=https://staging.aakashrealtor.com/api/v1
class AppConfig {
  AppConfig._();

  /// Base URL for the REST API. Every endpoint path is appended to this.
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://api.aakashrealtor.com/api/v1',
  );

  /// Connect / receive timeouts.
  static const Duration connectTimeout = Duration(seconds: 20);
  static const Duration receiveTimeout = Duration(seconds: 25);

  /// Secure-storage key for the Sanctum bearer token.
  static const String tokenStorageKey = 'aakash_auth_token';

  /// Default page size for paginated lists.
  static const int defaultPerPage = 15;
}
