import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'config.dart';

/// Thin wrapper around [FlutterSecureStorage] for the auth bearer token.
///
/// Tokens are stored in the platform keystore/keychain — never in plain
/// shared-preferences — so they survive restarts but stay encrypted at rest.
class TokenStorage {
  TokenStorage([FlutterSecureStorage? storage])
      : _storage = storage ??
            const FlutterSecureStorage(
              aOptions: AndroidOptions(encryptedSharedPreferences: true),
              iOptions: IOSOptions(
                accessibility: KeychainAccessibility.first_unlock,
              ),
            );

  final FlutterSecureStorage _storage;

  Future<String?> read() => _storage.read(key: AppConfig.tokenStorageKey);

  Future<void> write(String token) =>
      _storage.write(key: AppConfig.tokenStorageKey, value: token);

  Future<void> clear() => _storage.delete(key: AppConfig.tokenStorageKey);

  Future<bool> hasToken() async => (await read()) != null;
}
