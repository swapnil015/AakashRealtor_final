import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/token_storage.dart';
import '../models/user.dart';
import '../services/auth_service.dart';
import 'providers.dart';

/// Authentication state for the whole app.
enum AuthStatus { unknown, authenticated, unauthenticated }

class AuthState {
  const AuthState({required this.status, this.user});

  final AuthStatus status;
  final User? user;

  bool get isAuthenticated => status == AuthStatus.authenticated;

  AuthState copyWith({AuthStatus? status, User? user, bool clearUser = false}) {
    return AuthState(
      status: status ?? this.status,
      user: clearUser ? null : (user ?? this.user),
    );
  }

  static const unknown = AuthState(status: AuthStatus.unknown);
}

/// Controls login / register / logout and exposes the current [AuthState].
///
/// On construction it attempts to restore a session from the stored token,
/// which drives the router's redirect logic and the splash gate.
class AuthController extends StateNotifier<AuthState> {
  AuthController({
    required AuthService authService,
    required TokenStorage tokenStorage,
  })  : _authService = authService,
        _tokenStorage = tokenStorage,
        super(AuthState.unknown) {
    _restore();
  }

  final AuthService _authService;
  final TokenStorage _tokenStorage;

  /// Try to restore a session on launch by validating the stored token.
  Future<void> _restore() async {
    final hasToken = await _tokenStorage.hasToken();
    if (!hasToken) {
      state = const AuthState(status: AuthStatus.unauthenticated);
      return;
    }
    try {
      final user = await _authService.me();
      state = AuthState(status: AuthStatus.authenticated, user: user);
    } catch (_) {
      // Token invalid/expired — clear it and fall back to logged-out.
      await _tokenStorage.clear();
      state = const AuthState(status: AuthStatus.unauthenticated);
    }
  }

  Future<void> login({required String login, required String password}) async {
    final result = await _authService.login(login: login, password: password);
    state = AuthState(status: AuthStatus.authenticated, user: result.user);
  }

  Future<void> register({
    required String name,
    required String email,
    required String phone,
    required String password,
    required String passwordConfirmation,
  }) async {
    final result = await _authService.register(
      name: name,
      email: email,
      phone: phone,
      password: password,
      passwordConfirmation: passwordConfirmation,
    );
    state = AuthState(status: AuthStatus.authenticated, user: result.user);
  }

  Future<void> logout() async {
    await _authService.logout();
    state = const AuthState(status: AuthStatus.unauthenticated);
  }

  /// Called by the API client's 401 handler — clears state without a network
  /// round-trip (the token is already invalid).
  Future<void> forceLogout() async {
    await _tokenStorage.clear();
    state = const AuthState(status: AuthStatus.unauthenticated);
  }
}

final authControllerProvider =
    StateNotifierProvider<AuthController, AuthState>((ref) {
  return AuthController(
    authService: ref.watch(authServiceProvider),
    tokenStorage: ref.watch(tokenStorageProvider),
  );
});
