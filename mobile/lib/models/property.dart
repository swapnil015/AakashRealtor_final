import 'amenity.dart';
import 'json_utils.dart';

/// Price object: { amount, unit, formatted }.
class Price {
  Price({required this.amount, required this.unit, required this.formatted});

  final double amount;
  final String unit; // e.g. "total", "per month", "per sq.ft"
  final String formatted; // pre-formatted display string from the API

  factory Price.fromJson(dynamic json) {
    final map = asMap(json);
    if (map == null) {
      // Sometimes price arrives as a bare number.
      final amount = asDouble(json);
      return Price(amount: amount, unit: '', formatted: amount.toString());
    }
    return Price(
      amount: asDouble(map['amount']),
      unit: asString(map['unit']),
      formatted: asString(map['formatted']),
    );
  }

  /// Display string with a sensible fallback if `formatted` is empty.
  String get display => formatted.isNotEmpty ? formatted : amount.toString();
}

/// Property specs: { bedrooms, bathrooms, area, ... }.
class Specs {
  Specs({
    this.bedrooms,
    this.bathrooms,
    this.area,
    this.areaUnit,
    this.parking,
    this.floors,
  });

  final int? bedrooms;
  final int? bathrooms;
  final double? area;
  final String? areaUnit;
  final int? parking;
  final int? floors;

  factory Specs.fromJson(dynamic json) {
    final map = asMap(json) ?? const {};
    return Specs(
      bedrooms: asIntOrNull(map['bedrooms']),
      bathrooms: asIntOrNull(map['bathrooms']),
      area: asDoubleOrNull(map['area']),
      areaUnit: map['area_unit'] == null ? null : asString(map['area_unit']),
      parking: asIntOrNull(map['parking']),
      floors: asIntOrNull(map['floors']),
    );
  }
}

/// City reference embedded inside location.
class LocationCity {
  LocationCity({required this.name, required this.publicId});
  final String name;
  final String publicId;

  factory LocationCity.fromJson(dynamic json) {
    final map = asMap(json) ?? const {};
    return LocationCity(
      name: asString(map['name']),
      publicId: asString(map['public_id'], asString(map['id'])),
    );
  }
}

/// Property location: { address, city{}, latitude, longitude }.
class PropertyLocation {
  PropertyLocation({
    required this.address,
    required this.city,
    this.latitude,
    this.longitude,
  });

  final String address;
  final LocationCity city;
  final double? latitude;
  final double? longitude;

  factory PropertyLocation.fromJson(dynamic json) {
    final map = asMap(json) ?? const {};
    return PropertyLocation(
      address: asString(map['address']),
      city: LocationCity.fromJson(map['city']),
      latitude: asDoubleOrNull(map['latitude']),
      longitude: asDoubleOrNull(map['longitude']),
    );
  }

  bool get hasCoordinates => latitude != null && longitude != null;
}

/// A single image with its responsive size variants.
class PropertyImage {
  PropertyImage({required this.url, this.sizes = const {}});

  final String url;
  final Map<String, String> sizes;

  factory PropertyImage.fromJson(dynamic json) {
    if (json is String) return PropertyImage(url: json);
    final map = asMap(json) ?? const {};
    final sizesRaw = asMap(map['sizes']) ?? const {};
    return PropertyImage(
      url: asString(map['url']),
      sizes: sizesRaw.map((k, v) => MapEntry(k, asString(v))),
    );
  }

  /// Prefer a medium/thumb variant for cards; fall back to full url.
  String get thumb => sizes['thumb'] ?? sizes['small'] ?? sizes['medium'] ?? url;
}

/// Agent contact embedded in a property: { name, phone }.
class Agent {
  Agent({required this.name, this.phone, this.email, this.avatarUrl});

  final String name;
  final String? phone;
  final String? email;
  final String? avatarUrl;

  factory Agent.fromJson(dynamic json) {
    final map = asMap(json) ?? const {};
    return Agent(
      name: asString(map['name']),
      phone: map['phone'] == null ? null : asString(map['phone']),
      email: map['email'] == null ? null : asString(map['email']),
      avatarUrl: map['avatar_url'] == null ? null : asString(map['avatar_url']),
    );
  }
}

/// The core Property model. Handles both list (summary) and detail shapes;
/// detail-only fields (images, amenities, similar) are simply empty in list
/// responses.
class Property {
  Property({
    required this.id,
    required this.title,
    required this.slug,
    required this.price,
    required this.transactionType,
    required this.specs,
    required this.location,
    required this.flags,
    this.primaryImage,
    this.images = const [],
    this.amenities = const [],
    this.agent,
    this.description,
    this.similar = const [],
  });

  final int id;
  final String title;
  final String slug;
  final Price price;

  /// "buy" or "rent".
  final String transactionType;
  final Specs specs;
  final PropertyLocation location;

  /// Arbitrary boolean flags: featured, exclusive, open_house, by_owner, etc.
  final Map<String, bool> flags;

  final String? primaryImage;
  final List<PropertyImage> images;
  final List<Amenity> amenities;
  final Agent? agent;
  final String? description;

  /// Similar properties (detail response only).
  final List<Property> similar;

  factory Property.fromJson(Map<String, dynamic> json) {
    return Property(
      id: asInt(json['id']),
      title: asString(json['title']),
      slug: asString(json['slug']),
      price: Price.fromJson(json['price']),
      transactionType: asString(json['transaction_type']),
      specs: Specs.fromJson(json['specs']),
      location: PropertyLocation.fromJson(json['location']),
      flags: _parseFlags(json['flags']),
      primaryImage:
          json['primary_image'] == null ? null : asString(json['primary_image']),
      images: asList(json['images'], PropertyImage.fromJson),
      amenities: asList(json['amenities'], Amenity.fromJson),
      agent: json['agent'] == null ? null : Agent.fromJson(json['agent']),
      description:
          json['description'] == null ? null : asString(json['description']),
      similar: asList(
        json['similar'],
        (e) => Property.fromJson(asMap(e) ?? const {}),
      ),
    );
  }

  static Map<String, bool> _parseFlags(dynamic raw) {
    final map = asMap(raw);
    if (map == null) return const {};
    return map.map((k, v) => MapEntry(k, asBool(v)));
  }

  // ---- Convenience getters ----

  bool flag(String key) => flags[key] == true;
  bool get isFeatured => flag('featured');
  bool get isExclusive => flag('exclusive');
  bool get isByOwner => flag('by_owner');
  bool get isOpenHouse => flag('open_house');
  bool get isSold => flag('sold');
  bool get isRent => transactionType == 'rent';

  /// Best image url for a card: primary_image -> first image -> placeholder.
  String? get displayImage {
    if (primaryImage != null && primaryImage!.isNotEmpty) return primaryImage;
    if (images.isNotEmpty) return images.first.thumb;
    return null;
  }

  /// All gallery image urls (full size), falling back to primary if empty.
  List<String> get galleryUrls {
    if (images.isNotEmpty) return images.map((i) => i.url).toList();
    if (primaryImage != null) return [primaryImage!];
    return const [];
  }
}
