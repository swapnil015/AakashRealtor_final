import 'json_utils.dart';

/// A city returned by GET /cities (and embedded inside property.location.city).
class City {
  City({
    required this.publicId,
    required this.name,
    this.propertyCount,
  });

  /// City identifier used as the `city` query param on /properties.
  final String publicId;
  final String name;
  final int? propertyCount;

  factory City.fromJson(Map<String, dynamic> json) {
    return City(
      publicId: asString(json['public_id'], asString(json['id'])),
      name: asString(json['name']),
      propertyCount: asIntOrNull(json['property_count'] ?? json['properties_count']),
    );
  }

  @override
  bool operator ==(Object other) =>
      other is City && other.publicId == publicId;

  @override
  int get hashCode => publicId.hashCode;
}
