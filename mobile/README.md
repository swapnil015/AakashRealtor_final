# Aakash Realtor — Mobile (Flutter)

Cross-platform (iOS + Android) app consuming the same `/api/v1` REST API as the
web frontend. Riverpod state, go_router navigation, Dio HTTP, secure token
storage, FCM push scaffolding.

## Requirements

- Flutter 3.22+ / Dart 3.4+
- The backend API running (see `../backend`)
- (Optional) Firebase project for push notifications

## Quick start

```bash
cd mobile
flutter pub get

# point the app at your API (defaults to https://api.aakashrealtor.com/api/v1)
flutter run --dart-define=API_BASE=http://10.0.2.2:8000/api/v1
```

> On the Android emulator use `10.0.2.2` to reach the host machine's `localhost`.
> The base URL is read in `lib/core/config.dart` via `--dart-define=API_BASE=...`.

## Project structure

```
mobile/lib/
├── main.dart                 # bootstrap + ProviderScope
├── app.dart                  # MaterialApp.router + theme
├── core/
│   ├── api_client.dart        # Dio + envelope unwrapping + bearer interceptor
│   ├── api_response.dart      # { success, data, meta } model
│   ├── api_exception.dart     # typed errors (validation messages)
│   ├── token_storage.dart     # flutter_secure_storage wrapper
│   ├── config.dart            # API base URL (dart-define)
│   └── theme.dart             # gold #C9A227 brand theme
├── models/                    # property, user, city, category, amenity (fromJson)
├── services/                  # auth, property, lead, favorite, notification
├── providers/                 # Riverpod providers (auth, home, search, detail, …)
├── router/app_router.dart     # go_router routes + auth redirect
├── widgets/                   # property_card, main_shell (bottom nav), common
└── features/
    ├── onboarding/
    ├── home/                  # Featured/Exclusive/Latest/Emerging/By-Owner/Open House
    ├── search/                # search + filters_sheet (txn, category, city, price, beds)
    ├── property_detail/       # gallery, specs, amenities, map, inquiry_form
    ├── favorites/
    ├── post_property/         # multi-step + camera/gallery image upload
    ├── auth/                  # login, register, account
    ├── my_listings/
    └── inquiries/
```

## Auth

Sanctum bearer tokens. On login/register the token is stored via
`flutter_secure_storage` and attached by the Dio interceptor. `go_router`
redirects guests away from protected routes (post property, my listings,
favorites, inquiries).

## Push notifications

`lib/services/notification_service.dart` scaffolds `firebase_messaging`:
- registers the FCM token (send it to the API once a token-registration
  endpoint is added),
- handles foreground/background messages for **new matching listing** and
  **inquiry reply** events.

To enable: create a Firebase project, add `google-services.json`
(android/app) and `GoogleService-Info.plist` (ios/Runner), then run
`flutterfire configure`.

## Notes

- Hand-authored to current Flutter/Riverpod/go_router conventions; run
  `flutter pub get` then `flutter analyze` before building.
- Image upload uses `image_picker` (camera + gallery) and posts multipart to
  `POST /properties/{id}/images`.
