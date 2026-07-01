import '../core/api_client.dart';
import '../core/token_storage.dart';
import '../models/json_utils.dart';
import '../models/user.dart';

/// Wraps the /auth/* endpoints and persists the bearer token.
class AuthService {
  AuthService({required ApiClient client, required TokenStorage tokenStorage})
      : _client = client,
        _tokenStorage = tokenStorage;

  final ApiClient _client;
  final TokenStorage _tokenStorage;

  /// POST /auth/register {name,email,phone,password,password_confirmation}
  Future<AuthResult> register({
    required String name,
    required String email,
    required String phone,
    required String password,
    required String passwordConfirmation,
  }) async {
    final res = await _client.postJson(
      '/auth/register',
      body: {
        'name': name,
        'email': email,
        'phone': phone,
        'password': password,
        'password_confirmation': passwordConfirmation,
      },
      decoder: (data) => AuthResult.fromJson(asMap(data) ?? const {}),
    );
    await _tokenStorage.write(res.data.token);
    return res.data;
  }

  /// POST /auth/login {login(email or phone), password}
  Future<AuthResult> login({
    required String login,
    required String password,
  }) async {
    final res = await _client.postJson(
      '/auth/login',
      body: {'login': login, 'password': password},
      decoder: (data) => AuthResult.fromJson(asMap(data) ?? const {}),
    );
    await _tokenStorage.write(res.data.token);
    return res.data;
  }

  /// GET /auth/me — used to restore the session on launch.
  Future<User> me() async {
    final res = await _client.getJson(
      '/auth/me',
      decoder: (data) => User.fromJson(asMap(data) ?? const {}),
    );
    return res.data;
  }

  /// POST /auth/logout — best-effort; token is cleared locally regardless.
  Future<void> logout() async {
    try {
      await _client.postJson('/auth/logout', decoder: (_) => null);
    } catch (_) {
      // Even if the network call fails, clear the local token below.
    }
    await _tokenStorage.clear();
  }
}
