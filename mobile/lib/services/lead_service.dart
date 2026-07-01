import '../core/api_client.dart';

/// Posts inquiries (about a specific property) and requirements
/// (general "I'm looking for…" leads).
class LeadService {
  LeadService(this._client);
  final ApiClient _client;

  /// POST /inquiries {property_id,name,phone,email,message}
  Future<String> sendInquiry({
    required int propertyId,
    required String name,
    required String phone,
    String? email,
    required String message,
  }) async {
    final res = await _client.postJson(
      '/inquiries',
      body: {
        'property_id': propertyId,
        'name': name,
        'phone': phone,
        'email': email,
        'message': message,
      },
      decoder: (_) => null,
    );
    return res.message;
  }

  /// POST /requirements {name,phone,transaction_type,category_id,city_id,
  /// min_budget,max_budget,message}
  Future<String> sendRequirement({
    required String name,
    required String phone,
    required String transactionType,
    int? categoryId,
    String? cityId,
    num? minBudget,
    num? maxBudget,
    String? message,
  }) async {
    final res = await _client.postJson(
      '/requirements',
      body: {
        'name': name,
        'phone': phone,
        'transaction_type': transactionType,
        'category_id': categoryId,
        'city_id': cityId,
        'min_budget': minBudget,
        'max_budget': maxBudget,
        'message': message,
      },
      decoder: (_) => null,
    );
    return res.message;
  }

  /// GET /my/inquiries (auth) — the current user's submitted inquiries.
  ///
  /// Returns the raw decoded list; the inquiries feature renders these
  /// loosely-typed maps since there is no dedicated detail model required.
  Future<List<Map<String, dynamic>>> myInquiries() async {
    final res = await _client.getJson(
      '/my/inquiries',
      decoder: (data) {
        if (data is! List) return <Map<String, dynamic>>[];
        return data
            .whereType<Map>()
            .map((e) => Map<String, dynamic>.from(e))
            .toList();
      },
    );
    return res.data;
  }
}
