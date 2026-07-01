import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme.dart';
import '../../providers/search_provider.dart';
import '../../router/app_router.dart';
import '../../services/property_service.dart';
import '../../widgets/common.dart';
import '../../widgets/property_card.dart';
import 'filters_sheet.dart';

/// Search + filters + paginated results list.
class SearchScreen extends ConsumerStatefulWidget {
  const SearchScreen({super.key});

  @override
  ConsumerState<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends ConsumerState<SearchScreen> {
  final _scrollController = ScrollController();
  final _queryController = TextEditingController();

  @override
  void initState() {
    super.initState();
    // Load initial results once after first frame.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final state = ref.read(searchControllerProvider);
      if (state.items.isEmpty && !state.isLoading) {
        ref.read(searchControllerProvider.notifier).refresh();
      }
    });
    _scrollController.addListener(_onScroll);
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 300) {
      ref.read(searchControllerProvider.notifier).loadMore();
    }
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _queryController.dispose();
    super.dispose();
  }

  Future<void> _openFilters() async {
    final current = ref.read(searchControllerProvider).filters;
    final result = await showModalBottomSheet<PropertyFilters>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.bg,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(AppRadii.lg)),
      ),
      builder: (_) => FiltersSheet(initial: current),
    );
    if (result != null) {
      ref.read(searchControllerProvider.notifier).applyFilters(result);
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(searchControllerProvider);
    final notifier = ref.read(searchControllerProvider.notifier);

    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            _searchBar(notifier),
            _filterSummary(state.filters),
            Expanded(child: _body(state, notifier)),
          ],
        ),
      ),
    );
  }

  Widget _searchBar(SearchController notifier) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(
          AppSpacing.md, AppSpacing.md, AppSpacing.md, AppSpacing.sm),
      child: Row(
        children: [
          Expanded(
            child: TextField(
              controller: _queryController,
              textInputAction: TextInputAction.search,
              onSubmitted: (v) => notifier.search(v.trim()),
              decoration: InputDecoration(
                hintText: 'Search by location, title…',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _queryController.text.isEmpty
                    ? null
                    : IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () {
                          _queryController.clear();
                          notifier.search('');
                        },
                      ),
              ),
            ),
          ),
          const SizedBox(width: AppSpacing.sm),
          Material(
            color: AppColors.ink,
            borderRadius: BorderRadius.circular(AppRadii.md),
            child: InkWell(
              borderRadius: BorderRadius.circular(AppRadii.md),
              onTap: _openFilters,
              child: const Padding(
                padding: EdgeInsets.all(14),
                child: Icon(Icons.tune, color: Colors.white),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _filterSummary(PropertyFilters filters) {
    final chips = <String>[];
    chips.add(filters.transactionType == 'rent' ? 'Rent' : 'Buy');
    if (filters.categorySlug != null) chips.add(filters.categorySlug!);
    if (filters.cityPublicId != null) chips.add('City');
    if (filters.bedrooms != null) chips.add('${filters.bedrooms} bed');
    if (filters.minPrice != null || filters.maxPrice != null) {
      chips.add('Price');
    }

    return SizedBox(
      height: 40,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
        itemCount: chips.length,
        separatorBuilder: (_, __) => const SizedBox(width: 6),
        itemBuilder: (_, i) => Chip(
          label: Text(chips[i]),
          visualDensity: VisualDensity.compact,
          backgroundColor: AppColors.surface,
        ),
      ),
    );
  }

  Widget _body(SearchState state, SearchController notifier) {
    if (state.isLoading) return const LoadingView();
    if (state.error != null && state.items.isEmpty) {
      return ErrorView(message: state.error!, onRetry: notifier.refresh);
    }
    if (state.items.isEmpty) {
      return const EmptyView(
        icon: Icons.search_off,
        title: 'No properties found',
        message: 'Try widening your filters or searching a different area.',
      );
    }

    return RefreshIndicator(
      color: AppColors.gold,
      onRefresh: notifier.refresh,
      child: ListView.separated(
        controller: _scrollController,
        padding: const EdgeInsets.fromLTRB(
            AppSpacing.md, AppSpacing.sm, AppSpacing.md, AppSpacing.lg),
        itemCount: state.items.length + 1,
        separatorBuilder: (_, __) => const SizedBox(height: AppSpacing.md),
        itemBuilder: (context, i) {
          if (i == state.items.length) {
            // Footer: loading-more spinner or end marker.
            if (state.isLoadingMore) {
              return const Padding(
                padding: EdgeInsets.all(AppSpacing.md),
                child: Center(
                    child: CircularProgressIndicator(color: AppColors.gold)),
              );
            }
            return Padding(
              padding: const EdgeInsets.all(AppSpacing.md),
              child: Center(
                child: Text(
                  '${state.total} properties',
                  style: const TextStyle(color: AppColors.muted),
                ),
              ),
            );
          }
          final p = state.items[i];
          return PropertyCard(
            property: p,
            onTap: () => context.push(Routes.propertyDetail(p.slug)),
          );
        },
      ),
    );
  }
}
