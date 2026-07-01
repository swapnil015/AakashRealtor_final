import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../router/app_router.dart';

/// Scaffold with the bottom navigation bar that wraps the four primary tabs:
/// Home, Search, Saved, Account. Uses GoRouter to switch branches.
class MainShell extends StatelessWidget {
  const MainShell({super.key, required this.state, required this.child});

  final GoRouterState state;
  final Widget child;

  static const _tabs = [
    Routes.home,
    Routes.search,
    Routes.favorites,
    Routes.account,
  ];

  int _indexFor(String location) {
    if (location.startsWith(Routes.search)) return 1;
    if (location.startsWith(Routes.favorites)) return 2;
    if (location.startsWith(Routes.account)) return 3;
    return 0;
  }

  @override
  Widget build(BuildContext context) {
    final index = _indexFor(state.matchedLocation);
    return Scaffold(
      body: child,
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: index,
        onTap: (i) => context.go(_tabs[i]),
        items: const [
          BottomNavigationBarItem(
              icon: Icon(Icons.home_outlined),
              activeIcon: Icon(Icons.home),
              label: 'Home'),
          BottomNavigationBarItem(
              icon: Icon(Icons.search_outlined),
              activeIcon: Icon(Icons.search),
              label: 'Search'),
          BottomNavigationBarItem(
              icon: Icon(Icons.favorite_border),
              activeIcon: Icon(Icons.favorite),
              label: 'Saved'),
          BottomNavigationBarItem(
              icon: Icon(Icons.person_outline),
              activeIcon: Icon(Icons.person),
              label: 'Account'),
        ],
      ),
    );
  }
}
