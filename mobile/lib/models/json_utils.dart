/// Small, null-safe JSON coercion helpers shared by all models.
///
/// The API is fairly permissive (numbers sometimes arrive as strings,
/// nested objects may be absent), so models lean on these to stay robust.

int? asIntOrNull(dynamic v) {
  if (v == null) return null;
  if (v is int) return v;
  if (v is double) return v.toInt();
  return int.tryParse('$v');
}

int asInt(dynamic v, [int fallback = 0]) => asIntOrNull(v) ?? fallback;

double? asDoubleOrNull(dynamic v) {
  if (v == null) return null;
  if (v is num) return v.toDouble();
  return double.tryParse('$v');
}

double asDouble(dynamic v, [double fallback = 0]) =>
    asDoubleOrNull(v) ?? fallback;

String asString(dynamic v, [String fallback = '']) =>
    v == null ? fallback : '$v';

bool asBool(dynamic v, [bool fallback = false]) {
  if (v is bool) return v;
  if (v is num) return v != 0;
  if (v is String) return v == 'true' || v == '1';
  return fallback;
}

/// Safely cast a dynamic value to `Map<String, dynamic>` (or null).
Map<String, dynamic>? asMap(dynamic v) =>
    v is Map ? Map<String, dynamic>.from(v) : null;

/// Safely map a JSON list to a typed list using [mapper].
List<T> asList<T>(dynamic v, T Function(dynamic item) mapper) {
  if (v is! List) return <T>[];
  return v.map(mapper).toList();
}
