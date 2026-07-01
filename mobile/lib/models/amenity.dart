import 'json_utils.dart';

/// An amenity (e.g. Parking, Lift, Gym) from GET /amenities or embedded in a
/// property's `amenities[]` array.
class Amenity {
  Amenity({required this.id, required this.name, this.icon});

  final int id;
  final String name;
  final String? icon;

  factory Amenity.fromJson(dynamic json) {
    // Amenities may arrive as a plain string or as an object.
    if (json is String) {
      return Amenity(id: json.hashCode, name: json);
    }
    final map = asMap(json) ?? const {};
    return Amenity(
      id: asInt(map['id']),
      name: asString(map['name']),
      icon: map['icon'] == null ? null : asString(map['icon']),
    );
  }
}
