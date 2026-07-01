import 'package:dio/dio.dart';
import 'package:http_parser/http_parser.dart';

import '../core/api_client.dart';
import '../core/api_response.dart';
import '../models/amenity.dart';
import '../models/category.dart';
import '../models/city.dart';
import '../models/json_utils.dart';
import '../models/property.dart';

/// Immutable filter set for the property listing endpoint.
class PropertyFilters {
  const PropertyFilters({
    this.transactionType,
    this.categorySlug,
    this.cityPublicId,
    this.minPrice,
    this.maxPrice,
    this.bedrooms,
    this.bathrooms,
    this.query,
    this.sort,
  });

  final String? transactionType; // buy | rent
  final String? categorySlug;
  final String? cityPublicId;
  final num? minPrice;
  final num? maxPrice;
  final int? bedrooms;
  final int? bathrooms;
  final String? query;
  final String? sort; // newest | price_asc | price_desc | popular

  Map<String, dynamic> toQuery() => {
        'transaction_type': transactionType,
        'category': categorySlug,
        'city': cityPublicId,
        'min_price': minPrice,
        'max_price': maxPrice,
        'bedrooms': bedrooms,
        'bathrooms': bathrooms,
        'q': query,
        'sort': sort,
      };

  PropertyFilters copyWith({
    Object? transactionType = _sentinel,
    Object? categorySlug = _sentinel,
    Object? cityPublicId = _sentinel,
    Object? minPrice = _sentinel,
    Object? maxPrice = _sentinel,
    Object? bedrooms = _sentinel,
    Object? bathrooms = _sentinel,
    Object? query = _sentinel,
    Object? sort = _sentinel,
  }) {
    return PropertyFilters(
      transactionType: transactionType == _sentinel
          ? this.transactionType
          : transactionType as String?,
      categorySlug:
          categorySlug == _sentinel ? this.categorySlug : categorySlug as String?,
      cityPublicId: cityPublicId == _sentinel
          ? this.cityPublicId
          : cityPublicId as String?,
      minPrice: minPrice == _sentinel ? this.minPrice : minPrice as num?,
      maxPrice: maxPrice == _sentinel ? this.maxPrice : maxPrice as num?,
      bedrooms: bedrooms == _sentinel ? this.bedrooms : bedrooms as int?,
      bathrooms: bathrooms == _sentinel ? this.bathrooms : bathrooms as int?,
      query: query == _sentinel ? this.query : query as String?,
      sort: sort == _sentinel ? this.sort : sort as String?,
    );
  }

  static const Object _sentinel = Object();
}

/// A page of properties plus its pagination metadata.
class PropertyPage {
  PropertyPage({required this.items, required this.pagination});
  final List<Property> items;
  final Pagination? pagination;
}

/// Reads property data, taxonomies, and posts new listings.
class PropertyService {
  PropertyService(this._client);
  final ApiClient _client;

  List<Property> _decodeList(dynamic data) =>
      asList(data, (e) => Property.fromJson(asMap(e) ?? const {}));

  /// GET /properties with filters + pagination.
  Future<PropertyPage> list(
    PropertyFilters filters, {
    int page = 1,
    int perPage = 15,
  }) async {
    final query = filters.toQuery()
      ..['page'] = page
      ..['per_page'] = perPage;
    final res = await _client.getJson(
      '/properties',
      query: query,
      decoder: _decodeList,
    );
    return PropertyPage(items: res.data, pagination: res.pagination);
  }

  /// Shared helper for the curated collection endpoints.
  Future<List<Property>> _collection(String path) async {
    final res = await _client.getJson(path, decoder: _decodeList);
    return res.data;
  }

  Future<List<Property>> featured() => _collection('/properties/featured');
  Future<List<Property>> exclusive() => _collection('/properties/exclusive');
  Future<List<Property>> emerging() => _collection('/properties/emerging');
  Future<List<Property>> openHouse() => _collection('/properties/open-house');
  Future<List<Property>> byOwner() => _collection('/properties/by-owner');
  Future<List<Property>> sold() => _collection('/properties/sold');

  /// GET /properties/{slug} — full detail.
  Future<Property> detail(String slug) async {
    final res = await _client.getJson(
      '/properties/$slug',
      decoder: (data) => Property.fromJson(asMap(data) ?? const {}),
    );
    return res.data;
  }

  /// GET /my/properties (auth).
  Future<PropertyPage> myProperties({int page = 1, int perPage = 15}) async {
    final res = await _client.getJson(
      '/my/properties',
      query: {'page': page, 'per_page': perPage},
      decoder: _decodeList,
    );
    return PropertyPage(items: res.data, pagination: res.pagination);
  }

  // ---- Taxonomies ----

  Future<List<City>> cities() async {
    final res = await _client.getJson(
      '/cities',
      decoder: (data) => asList(data, (e) => City.fromJson(asMap(e) ?? const {})),
    );
    return res.data;
  }

  Future<List<Category>> categories() async {
    final res = await _client.getJson(
      '/categories',
      decoder: (data) =>
          asList(data, (e) => Category.fromJson(asMap(e) ?? const {})),
    );
    return res.data;
  }

  Future<List<Amenity>> amenities() async {
    final res = await _client.getJson(
      '/amenities',
      decoder: (data) => asList(data, Amenity.fromJson),
    );
    return res.data;
  }

  /// POST /properties (auth, multipart) — create a listing with images.
  ///
  /// [imagePaths] are local file paths from image_picker; each is attached as
  /// `images[]`.
  Future<Property> create({
    required Map<String, dynamic> fields,
    required List<String> imagePaths,
  }) async {
    final formMap = <String, dynamic>{};
    fields.forEach((key, value) {
      if (value != null) formMap[key] = value;
    });

    final files = <MapEntry<String, MultipartFile>>[];
    for (final path in imagePaths) {
      files.add(
        MapEntry(
          'images[]',
          await MultipartFile.fromFile(
            path,
            filename: path.split(RegExp(r'[\\/]')).last,
            contentType: MediaType('image', 'jpeg'),
          ),
        ),
      );
    }

    final formData = FormData.fromMap(formMap);
    formData.files.addAll(files);

    final res = await _client.postMultipart(
      '/properties',
      formData: formData,
      decoder: (data) => Property.fromJson(asMap(data) ?? const {}),
    );
    return res.data;
  }
}
