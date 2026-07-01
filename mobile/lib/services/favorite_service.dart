import '../core/api_client.dart';
import '../models/json_utils.dart';
import '../models/property.dart';

/// Manages the authenticated user's saved/favorite properties.
class FavoriteService {
  FavoriteService(this._client);
  final ApiClient _client;

  /// GET /favorites (auth) — list of saved properties.
  Future<List<Property>> list() async {
    final res = await _client.getJson(
      '/favorites',
      decoder: (data) =>
          asList(data, (e) => Property.fromJson(asMap(e) ?? const {})),
    );
    return res.data;
  }

  /// POST /favorites/{property} — toggles favorite state.
  ///
  /// Returns the new favorited state if the API reports it, otherwise null.
  Future<bool?> toggle(int propertyId) async {
    final res = await _client.postJson(
      '/favorites/$propertyId',
      decoder: (data) {
        final map = asMap(data);
        if (map == null) return null;
        // Backend may return {favorited: true} or {is_favorite: true}.
        if (map.containsKey('favorited')) return asBool(map['favorited']);
        if (map.containsKey('is_favorite')) return asBool(map['is_favorite']);
        return null;
      },
    );
    return res.data;
  }
}
