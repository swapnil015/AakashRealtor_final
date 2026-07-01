import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme.dart';
import '../../providers/auth_provider.dart';
import '../../providers/favorites_provider.dart';
import '../../router/app_router.dart';
import '../../widgets/common.dart';
import '../../widgets/property_card.dart';

/// Saved / favorite properties for the signed-in user.
class FavoritesScreen extends ConsumerStatefulWidget {
  const FavoritesScreen({super.key});

  @override
  ConsumerState<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends ConsumerState<FavoritesScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (ref.read(authControllerProvider).isAuthenticated) {
        ref.read(favoritesControllerProvider.notifier).load();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authControllerProvider);
    final favorites = ref.watch(favoritesControllerProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Saved')),
      body: !auth.isAuthenticated
          ? EmptyView(
              icon: Icons.favorite_border,
              title: 'Sign in to save homes',
              message: 'Keep track of properties you love.',
              action: ElevatedButton(
                onPressed: () => context.push(Routes.login),
                child: const Text('Sign in'),
              ),
            )
          : favorites.items.isEmpty
              ? const EmptyView(
                  icon: Icons.favorite_border,
                  title: 'No saved properties yet',
                  message: 'Tap the heart on any listing to save it here.',
                )
              : RefreshIndicator(
                  color: AppColors.gold,
                  onRefresh: () =>
                      ref.read(favoritesControllerProvider.notifier).load(),
                  child: ListView.separated(
                    padding: const EdgeInsets.all(AppSpacing.md),
                    itemCount: favorites.items.length,
                    separatorBuilder: (_, __) =>
                        const SizedBox(height: AppSpacing.md),
                    itemBuilder: (_, i) {
                      final p = favorites.items[i];
                      return PropertyCard(
                        property: p,
                        onTap: () =>
                            context.push(Routes.propertyDetail(p.slug)),
                      );
                    },
                  ),
                ),
    );
  }
}
