import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../models/property.dart';
import '../services/property_service.dart';
import 'providers.dart';

/// Aggregated data for the home screen's curated carousels.
class HomeData {
  HomeData({
    required this.featured,
    required this.exclusive,
    required this.latest,
    required this.emerging,
    required this.byOwner,
    required this.openHouse,
  });

  final List<Property> featured;
  final List<Property> exclusive;
  final List<Property> latest;
  final List<Property> emerging;
  final List<Property> byOwner;
  final List<Property> openHouse;
}

/// Loads every home section in parallel. A failure in any single section is
/// swallowed to an empty list so the rest of the home screen still renders.
final homeDataProvider = FutureProvider<HomeData>((ref) async {
  final svc = ref.watch(propertyServiceProvider);

  Future<List<Property>> safe(Future<List<Property>> Function() fn) async {
    try {
      return await fn();
    } catch (_) {
      return <Property>[];
    }
  }

  // "Latest" reuses the main listing endpoint sorted by newest.
  Future<List<Property>> latest() async {
    final page = await svc.list(
      const PropertyFilters(sort: 'newest'),
      page: 1,
      perPage: 10,
    );
    return page.items;
  }

  final results = await Future.wait([
    safe(svc.featured),
    safe(svc.exclusive),
    safe(latest),
    safe(svc.emerging),
    safe(svc.byOwner),
    safe(svc.openHouse),
  ]);

  return HomeData(
    featured: results[0],
    exclusive: results[1],
    latest: results[2],
    emerging: results[3],
    byOwner: results[4],
    openHouse: results[5],
  );
});
