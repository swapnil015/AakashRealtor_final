import 'json_utils.dart';

/// Authenticated user from GET /auth/me and the auth responses.
class User {
  User({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.avatarUrl,
  });

  final int id;
  final String name;
  final String email;
  final String? phone;
  final String? avatarUrl;

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: asInt(json['id']),
      name: asString(json['name']),
      email: asString(json['email']),
      phone: json['phone'] == null ? null : asString(json['phone']),
      avatarUrl: json['avatar_url'] == null
          ? (json['avatar'] == null ? null : asString(json['avatar']))
          : asString(json['avatar_url']),
    );
  }

  String get initials {
    final parts = name.trim().split(RegExp(r'\s+'));
    if (parts.isEmpty || parts.first.isEmpty) return '?';
    if (parts.length == 1) return parts.first[0].toUpperCase();
    return (parts.first[0] + parts.last[0]).toUpperCase();
  }
}

/// Wraps the token + user pair returned by /auth/login & /auth/register.
class AuthResult {
  AuthResult({required this.token, required this.user});

  final String token;
  final User user;

  factory AuthResult.fromJson(Map<String, dynamic> json) {
    return AuthResult(
      token: asString(json['token']),
      // Some backends nest the user under `user`, others return it flat.
      user: User.fromJson(asMap(json['user']) ?? json),
    );
  }
}
