import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../models/property.dart';
import 'auth_provider.dart';
import 'providers.dart';

/// Favorites state: the list of saved properties plus a fast id-set for
/// "is this favorited?" checks on cards/detail.
class FavoritesState {
  const FavoritesState({this.items = const [], this.ids = const {}});

  final List<Property> items;
  final Set<int> ids;

  bool contains(int id) => ids.contains(id);

  FavoritesState copyWith({List<Property>? items, Set<int>? ids}) =>
      FavoritesState(items: items ?? this.items, ids: ids ?? this.ids);
}

class FavoritesController extends StateNotifier<FavoritesState> {
  FavoritesController(this._ref) : super(const FavoritesState());

  final Ref _ref;

  /// Load favorites from the server. Only meaningful when authenticated.
  Future<void> load() async {
    if (!_ref.read(authControllerProvider).isAuthenticated) {
      state = const FavoritesState();
      return;
    }
    final items = await _ref.read(favoriteServiceProvider).list();
    state = FavoritesState(items: items, ids: items.map((p) => p.id).toSet());
  }

  /// Optimistically toggle, then reconcile with the server response.
  Future<void> toggle(Property property) async {
    final id = property.id;
    final currentlyFav = state.contains(id);

    // Optimistic update.
    final newIds = Set<int>.from(state.ids);
    List<Property> newItems = List<Property>.from(state.items);
    if (currentlyFav) {
      newIds.remove(id);
      newItems.removeWhere((p) => p.id == id);
    } else {
      newIds.add(id);
      newItems = [property, ...newItems];
    }
    state = state.copyWith(ids: newIds, items: newItems);

    try {
      final serverState =
          await _ref.read(favoriteServiceProvider).toggle(id);
      // If the server disagrees with our optimistic guess, reconcile.
      if (serverState != null && serverState != !currentlyFav) {
        await load();
      }
    } catch (_) {
      // Roll back on failure.
      await load();
      rethrow;
    }
  }
}

final favoritesControllerProvider =
    StateNotifierProvider<FavoritesController, FavoritesState>((ref) {
  final controller = FavoritesController(ref);
  // Reload favorites whenever auth state flips to authenticated.
  ref.listen(authControllerProvider, (prev, next) {
    if (next.isAuthenticated && prev?.isAuthenticated != true) {
      controller.load();
    } else if (!next.isAuthenticated) {
      controller.state = const FavoritesState();
    }
  });
  return controller;
});
