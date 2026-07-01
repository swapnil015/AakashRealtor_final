import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api_client.dart';
import '../core/token_storage.dart';
import '../services/auth_service.dart';
import '../services/favorite_service.dart';
import '../services/lead_service.dart';
import '../services/notification_service.dart';
import '../services/property_service.dart';
import 'auth_provider.dart';

/// ---------------------------------------------------------------------------
/// Infrastructure providers (singletons for the app lifetime).
/// ---------------------------------------------------------------------------

final tokenStorageProvider = Provider<TokenStorage>((ref) => TokenStorage());

/// The Dio-backed API client. On 401 it triggers a forced logout so stale
/// sessions don't leave the user stuck.
final apiClientProvider = Provider<ApiClient>((ref) {
  final storage = ref.watch(tokenStorageProvider);
  return ApiClient(
    tokenStorage: storage,
    onUnauthorized: () {
      // Defer to avoid mutating state during a request build.
      Future.microtask(() => ref.read(authControllerProvider.notifier).forceLogout());
    },
  );
});

/// ---------------------------------------------------------------------------
/// Service providers.
/// ---------------------------------------------------------------------------

final authServiceProvider = Provider<AuthService>((ref) => AuthService(
      client: ref.watch(apiClientProvider),
      tokenStorage: ref.watch(tokenStorageProvider),
    ));

final propertyServiceProvider =
    Provider<PropertyService>((ref) => PropertyService(ref.watch(apiClientProvider)));

final leadServiceProvider =
    Provider<LeadService>((ref) => LeadService(ref.watch(apiClientProvider)));

final favoriteServiceProvider =
    Provider<FavoriteService>((ref) => FavoriteService(ref.watch(apiClientProvider)));

final notificationServiceProvider = Provider<NotificationService>(
    (ref) => NotificationService(ref.watch(apiClientProvider)));
