import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app.dart';

// NOTE: Firebase initialization is intentionally guarded so the app still runs
// in environments where Firebase hasn't been configured yet (see README).
// Uncomment the firebase_core import + initializeApp call once you've added the
// platform config files (google-services.json / GoogleService-Info.plist).
//
// import 'package:firebase_core/firebase_core.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // try {
  //   await Firebase.initializeApp();
  // } catch (e) {
  //   debugPrint('Firebase not configured yet: $e');
  // }

  runApp(const ProviderScope(child: AakashRealtorApp()));
}
