import 'json_utils.dart';

/// A property category (e.g. Apartment, Land, Villa) from GET /categories.
class Category {
  Category({
    required this.id,
    required this.slug,
    required this.name,
    this.icon,
  });

  final int id;
  final String slug;
  final String name;
  final String? icon;

  factory Category.fromJson(Map<String, dynamic> json) {
    return Category(
      id: asInt(json['id']),
      slug: asString(json['slug']),
      name: asString(json['name']),
      icon: json['icon'] == null ? null : asString(json['icon']),
    );
  }

  @override
  bool operator ==(Object other) => other is Category && other.id == id;

  @override
  int get hashCode => id.hashCode;
}
