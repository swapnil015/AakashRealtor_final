import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../models/property.dart';
import 'providers.dart';

/// Property detail by slug. Family-scoped so each slug caches independently.
final propertyDetailProvider =
    FutureProvider.family<Property, String>((ref, slug) async {
  return ref.watch(propertyServiceProvider).detail(slug);
});

/// The current user's listings (auth required).
final myPropertiesProvider = FutureProvider<List<Property>>((ref) async {
  final page = await ref.watch(propertyServiceProvider).myProperties();
  return page.items;
});

/// The current user's submitted inquiries (auth required).
final myInquiriesProvider =
    FutureProvider<List<Map<String, dynamic>>>((ref) async {
  return ref.watch(leadServiceProvider).myInquiries();
});
