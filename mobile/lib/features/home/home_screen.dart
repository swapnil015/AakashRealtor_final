import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme.dart';
import '../../models/property.dart';
import '../../providers/home_provider.dart';
import '../../router/app_router.dart';
import '../../widgets/common.dart';
import '../../widgets/property_card.dart';
import '../../widgets/section_header.dart';

/// Home screen: a vertical scroll of curated horizontal carousels.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(homeDataProvider);

    return Scaffold(
      body: SafeArea(
        child: RefreshIndicator(
          color: AppColors.gold,
          onRefresh: () async => ref.refresh(homeDataProvider.future),
          child: CustomScrollView(
            slivers: [
              _appBar(context),
              ...async.when(
                loading: () => [
                  const SliverToBoxAdapter(child: SizedBox(height: 16)),
                  const SliverToBoxAdapter(child: CardCarouselSkeleton()),
                  const SliverToBoxAdapter(child: SizedBox(height: 16)),
                  const SliverToBoxAdapter(child: CardCarouselSkeleton()),
                ],
                error: (e, _) => [
                  SliverFillRemaining(
                    hasScrollView: false,
                    child: ErrorView(
                      message: 'Could not load listings.',
                      onRetry: () => ref.refresh(homeDataProvider),
                    ),
                  ),
                ],
                data: (data) => _sections(context, data),
              ),
              const SliverToBoxAdapter(child: SizedBox(height: 24)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _appBar(BuildContext context) {
    return SliverAppBar(
      pinned: true,
      title: const _BrandTitle(),
      actions: [
        IconButton(
          icon: const Icon(Icons.search),
          onPressed: () => context.go(Routes.search),
        ),
      ],
    );
  }

  List<Widget> _sections(BuildContext context, HomeData data) {
    final sections = <Widget>[];

    void add(String title, String? subtitle, List<Property> items,
        {String? sort, bool large = false}) {
      if (items.isEmpty) return;
      sections.add(
        SliverToBoxAdapter(
          child: SectionHeader(
            title: title,
            subtitle: subtitle,
            onSeeAll: () => context.go(Routes.search),
          ),
        ),
      );
      sections.add(SliverToBoxAdapter(child: _Carousel(items: items)));
    }

    add('Featured', 'Hand-picked by our team', data.featured);
    add('Exclusive', 'Available only at Aakash Realtor', data.exclusive);
    add('Latest listings', 'Fresh on the market', data.latest);
    add('Emerging neighbourhoods', 'Up-and-coming areas', data.emerging);
    add('Open house', 'Visit this week', data.openHouse);
    add('By owner', 'Direct from property owners', data.byOwner);

    if (sections.isEmpty) {
      sections.add(
        const SliverFillRemaining(
          hasScrollView: false,
          child: EmptyView(
            icon: Icons.home_work_outlined,
            title: 'No listings yet',
            message: 'Check back soon for new properties.',
          ),
        ),
      );
    }
    return sections;
  }
}

class _Carousel extends StatelessWidget {
  const _Carousel({required this.items});
  final List<Property> items;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 252,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
        itemCount: items.length,
        separatorBuilder: (_, __) => const SizedBox(width: AppSpacing.md),
        itemBuilder: (_, i) {
          final p = items[i];
          return PropertyCard(
            property: p,
            horizontal: true,
            onTap: () => context.push(Routes.propertyDetail(p.slug)),
          );
        },
      ),
    );
  }
}

class _BrandTitle extends StatelessWidget {
  const _BrandTitle();

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 30,
          height: 30,
          decoration: BoxDecoration(
            color: AppColors.gold,
            borderRadius: BorderRadius.circular(8),
          ),
          alignment: Alignment.center,
          child: const Text('A',
              style: TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w900,
                  fontSize: 18)),
        ),
        const SizedBox(width: 8),
        const Text('Aakash Realtor',
            style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18)),
      ],
    );
  }
}
