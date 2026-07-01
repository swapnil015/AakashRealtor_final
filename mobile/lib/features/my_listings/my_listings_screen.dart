import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme.dart';
import '../../providers/detail_provider.dart';
import '../../router/app_router.dart';
import '../../widgets/common.dart';
import '../../widgets/property_card.dart';

/// The signed-in user's own listings (GET /my/properties).
class MyListingsScreen extends ConsumerWidget {
  const MyListingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(myPropertiesProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('My listings'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => context.push(Routes.postProperty),
          ),
        ],
      ),
      body: async.when(
        loading: () => const LoadingView(),
        error: (e, _) => ErrorView(
          message: 'Could not load your listings.',
          onRetry: () => ref.refresh(myPropertiesProvider),
        ),
        data: (items) => items.isEmpty
            ? EmptyView(
                icon: Icons.home_work_outlined,
                title: 'No listings yet',
                message: 'Post your first property to see it here.',
                action: ElevatedButton(
                  onPressed: () => context.push(Routes.postProperty),
                  child: const Text('Post a property'),
                ),
              )
            : RefreshIndicator(
                color: AppColors.gold,
                onRefresh: () async => ref.refresh(myPropertiesProvider.future),
                child: ListView.separated(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  itemCount: items.length,
                  separatorBuilder: (_, __) =>
                      const SizedBox(height: AppSpacing.md),
                  itemBuilder: (_, i) {
                    final p = items[i];
                    return PropertyCard(
                      property: p,
                      onTap: () => context.push(Routes.propertyDetail(p.slug)),
                    );
                  },
                ),
              ),
      ),
    );
  }
}
