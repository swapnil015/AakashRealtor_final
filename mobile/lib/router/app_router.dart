import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../features/auth/account_screen.dart';
import '../features/auth/login_screen.dart';
import '../features/auth/register_screen.dart';
import '../features/favorites/favorites_screen.dart';
import '../features/home/home_screen.dart';
import '../features/inquiries/inquiries_screen.dart';
import '../features/my_listings/my_listings_screen.dart';
import '../features/onboarding/onboarding_screen.dart';
import '../features/post_property/post_property_screen.dart';
import '../features/property_detail/property_detail_screen.dart';
import '../features/search/search_screen.dart';
import '../providers/auth_provider.dart';
import '../widgets/main_shell.dart';

/// Route name/path constants to avoid magic strings.
class Routes {
  Routes._();
  static const onboarding = '/onboarding';
  static const home = '/';
  static const search = '/search';
  static const favorites = '/favorites';
  static const account = '/account';
  static const login = '/login';
  static const register = '/register';
  static const postProperty = '/post';
  static const myListings = '/my-listings';
  static const inquiries = '/inquiries';

  static String propertyDetail(String slug) => '/property/$slug';
}

/// Routes that require authentication; visiting them while logged out
/// redirects to /login.
const _protectedPrefixes = [
  Routes.postProperty,
  Routes.myListings,
  Routes.inquiries,
  Routes.favorites,
];

final _rootKey = GlobalKey<NavigatorState>();
final _shellKey = GlobalKey<NavigatorState>();

/// The app router. It rebuilds when auth state changes so redirects re-evaluate.
final appRouterProvider = Provider<GoRouter>((ref) {
  final auth = ref.watch(authControllerProvider);

  return GoRouter(
    navigatorKey: _rootKey,
    initialLocation: Routes.home,
    refreshListenable: _AuthListenable(ref),
    redirect: (context, state) {
      final status = auth.status;
      // Wait for the session-restore to settle before redirecting.
      if (status == AuthStatus.unknown) return null;

      final loc = state.matchedLocation;
      final loggingIn = loc == Routes.login || loc == Routes.register;

      final needsAuth =
          _protectedPrefixes.any((p) => loc == p || loc.startsWith('$p/'));

      if (needsAuth && status != AuthStatus.authenticated) {
        return Routes.login;
      }
      // If already authenticated, keep the user out of the auth screens.
      if (loggingIn && status == AuthStatus.authenticated) {
        return Routes.home;
      }
      return null;
    },
    routes: [
      GoRoute(
        path: Routes.onboarding,
        builder: (_, __) => const OnboardingScreen(),
      ),
      GoRoute(
        path: Routes.login,
        builder: (_, __) => const LoginScreen(),
      ),
      GoRoute(
        path: Routes.register,
        builder: (_, __) => const RegisterScreen(),
      ),
      GoRoute(
        path: '/property/:slug',
        parentNavigatorKey: _rootKey,
        builder: (_, state) =>
            PropertyDetailScreen(slug: state.pathParameters['slug']!),
      ),
      GoRoute(
        path: Routes.postProperty,
        parentNavigatorKey: _rootKey,
        builder: (_, __) => const PostPropertyScreen(),
      ),
      GoRoute(
        path: Routes.myListings,
        parentNavigatorKey: _rootKey,
        builder: (_, __) => const MyListingsScreen(),
      ),
      GoRoute(
        path: Routes.inquiries,
        parentNavigatorKey: _rootKey,
        builder: (_, __) => const InquiriesScreen(),
      ),
      // Bottom-nav shell.
      ShellRoute(
        navigatorKey: _shellKey,
        builder: (context, state, child) =>
            MainShell(state: state, child: child),
        routes: [
          GoRoute(
            path: Routes.home,
            builder: (_, __) => const HomeScreen(),
          ),
          GoRoute(
            path: Routes.search,
            builder: (_, __) => const SearchScreen(),
          ),
          GoRoute(
            path: Routes.favorites,
            builder: (_, __) => const FavoritesScreen(),
          ),
          GoRoute(
            path: Routes.account,
            builder: (_, __) => const AccountScreen(),
          ),
        ],
      ),
    ],
  );
});

/// Bridges the Riverpod auth state to GoRouter's [Listenable] refresh hook.
class _AuthListenable extends ChangeNotifier {
  _AuthListenable(Ref ref) {
    ref.listen(authControllerProvider, (_, __) => notifyListeners());
  }
}
