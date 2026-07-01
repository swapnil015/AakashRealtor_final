import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/api_exception.dart';
import '../models/property.dart';
import '../services/property_service.dart';
import 'providers.dart';

/// Paginated, filterable property search state.
class SearchState {
  const SearchState({
    this.filters = const PropertyFilters(transactionType: 'buy'),
    this.items = const [],
    this.page = 1,
    this.hasMore = false,
    this.total = 0,
    this.isLoading = false,
    this.isLoadingMore = false,
    this.error,
  });

  final PropertyFilters filters;
  final List<Property> items;
  final int page;
  final bool hasMore;
  final int total;
  final bool isLoading;
  final bool isLoadingMore;
  final String? error;

  SearchState copyWith({
    PropertyFilters? filters,
    List<Property>? items,
    int? page,
    bool? hasMore,
    int? total,
    bool? isLoading,
    bool? isLoadingMore,
    Object? error = _sentinel,
  }) {
    return SearchState(
      filters: filters ?? this.filters,
      items: items ?? this.items,
      page: page ?? this.page,
      hasMore: hasMore ?? this.hasMore,
      total: total ?? this.total,
      isLoading: isLoading ?? this.isLoading,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      error: error == _sentinel ? this.error : error as String?,
    );
  }

  static const Object _sentinel = Object();
}

class SearchController extends StateNotifier<SearchState> {
  SearchController(this._service) : super(const SearchState());

  final PropertyService _service;

  /// Replace the active filters and reload from page 1.
  Future<void> applyFilters(PropertyFilters filters) async {
    state = state.copyWith(filters: filters);
    await refresh();
  }

  /// Update only the free-text query and reload.
  Future<void> search(String query) async {
    state = state.copyWith(
      filters: state.filters.copyWith(query: query.isEmpty ? null : query),
    );
    await refresh();
  }

  /// Load page 1 for the current filters.
  Future<void> refresh() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.list(state.filters, page: 1);
      state = state.copyWith(
        items: result.items,
        page: 1,
        hasMore: result.pagination?.hasMore ?? false,
        total: result.pagination?.total ?? result.items.length,
        isLoading: false,
      );
    } on ApiException catch (e) {
      state = state.copyWith(isLoading: false, error: e.message);
    }
  }

  /// Append the next page (infinite scroll).
  Future<void> loadMore() async {
    if (state.isLoadingMore || !state.hasMore) return;
    state = state.copyWith(isLoadingMore: true);
    try {
      final next = state.page + 1;
      final result = await _service.list(state.filters, page: next);
      state = state.copyWith(
        items: [...state.items, ...result.items],
        page: next,
        hasMore: result.pagination?.hasMore ?? false,
        isLoadingMore: false,
      );
    } on ApiException catch (e) {
      state = state.copyWith(isLoadingMore: false, error: e.message);
    }
  }
}

final searchControllerProvider =
    StateNotifierProvider<SearchController, SearchState>((ref) {
  return SearchController(ref.watch(propertyServiceProvider));
});
