/// Strongly-typed view over the standard API envelope:
/// ```json
/// {
///   "success": bool,
///   "data": ...,
///   "message": str,
///   "meta": { "pagination": { current_page, per_page, total, last_page, has_more } },
///   "errors": { field: [msg] }
/// }
/// ```
class ApiResponse<T> {
  ApiResponse({
    required this.success,
    required this.data,
    required this.message,
    this.pagination,
  });

  final bool success;
  final T data;
  final String message;
  final Pagination? pagination;
}

/// Pagination metadata extracted from `meta.pagination`.
class Pagination {
  Pagination({
    required this.currentPage,
    required this.perPage,
    required this.total,
    required this.lastPage,
    required this.hasMore,
  });

  final int currentPage;
  final int perPage;
  final int total;
  final int lastPage;
  final bool hasMore;

  factory Pagination.fromJson(Map<String, dynamic> json) {
    int asInt(dynamic v, [int fallback = 0]) =>
        v is int ? v : int.tryParse('$v') ?? fallback;
    return Pagination(
      currentPage: asInt(json['current_page'], 1),
      perPage: asInt(json['per_page'], 15),
      total: asInt(json['total']),
      lastPage: asInt(json['last_page'], 1),
      hasMore: json['has_more'] == true,
    );
  }
}
