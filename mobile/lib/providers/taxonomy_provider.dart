import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../models/category.dart';
import '../models/city.dart';
import 'providers.dart';

/// Cities are loaded once and cached for the app lifetime (used in filters).
final citiesProvider = FutureProvider<List<City>>((ref) async {
  return ref.watch(propertyServiceProvider).cities();
});

/// Categories are loaded once and cached for the app lifetime.
final categoriesProvider = FutureProvider<List<Category>>((ref) async {
  return ref.watch(propertyServiceProvider).categories();
});
