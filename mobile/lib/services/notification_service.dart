import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import '../core/api_client.dart';

/// Top-level background handler. Must be a top-level/static function so the
/// Firebase plugin can invoke it in a separate isolate.
@pragma('vm:entry-point')
Future<void> firebaseBackgroundHandler(RemoteMessage message) async {
  // TODO: handle data-only background pushes here if needed (e.g. badge count).
  debugPrint('[push] background message: ${message.messageId}');
}

/// Scaffolding for push notifications.
///
/// Two notification types are expected from the backend:
///   * `new_listing`    — a new property matching a saved requirement.
///   * `inquiry_reply`  — an agent replied to one of the user's inquiries.
///
/// Wiring to the backend device-registration endpoint and to in-app
/// navigation is intentionally left as TODOs for the platform integration.
class NotificationService {
  NotificationService(this._client);

  final ApiClient _client;
  final FirebaseMessaging _messaging = FirebaseMessaging.instance;

  /// Call once after Firebase.initializeApp() and after the user signs in.
  Future<void> init() async {
    // Background handler registration.
    FirebaseMessaging.onBackgroundMessage(firebaseBackgroundHandler);

    // Request OS permission (iOS / Android 13+).
    final settings = await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );
    debugPrint('[push] permission: ${settings.authorizationStatus}');

    // Foreground messages.
    FirebaseMessaging.onMessage.listen(_onForegroundMessage);

    // App opened from a notification (background -> foreground).
    FirebaseMessaging.onMessageOpenedApp.listen(_onMessageOpenedApp);

    // App opened from terminated state.
    final initial = await _messaging.getInitialMessage();
    if (initial != null) _onMessageOpenedApp(initial);

    // Register the device token with the backend.
    await registerToken();

    // Keep the backend in sync when the token rotates.
    _messaging.onTokenRefresh.listen((token) => _sendTokenToBackend(token));
  }

  /// Fetches the FCM token and registers it with the backend so the server
  /// can target this device for "new matching listing" / "inquiry reply".
  Future<void> registerToken() async {
    try {
      final token = await _messaging.getToken();
      if (token != null) await _sendTokenToBackend(token);
    } catch (e) {
      debugPrint('[push] failed to get FCM token: $e');
    }
  }

  Future<void> _sendTokenToBackend(String token) async {
    debugPrint('[push] FCM token: $token');
    // TODO: wire to the backend device-registration endpoint, e.g.:
    //
    // await _client.postJson(
    //   '/devices',
    //   body: {'fcm_token': token, 'platform': defaultTargetPlatform.name},
    //   decoder: (_) => null,
    // );
    //
    // The endpoint path is a backend assumption — adjust to the real route.
  }

  void _onForegroundMessage(RemoteMessage message) {
    debugPrint('[push] foreground: ${message.notification?.title}');
    // TODO: show an in-app banner / local notification.
  }

  void _onMessageOpenedApp(RemoteMessage message) {
    final type = message.data['type'];
    final slug = message.data['property_slug'];
    debugPrint('[push] opened: type=$type slug=$slug');
    // TODO: deep-link via GoRouter, e.g.:
    //   if (type == 'new_listing' && slug != null) router.go('/property/$slug');
    //   if (type == 'inquiry_reply') router.go('/inquiries');
  }
}
