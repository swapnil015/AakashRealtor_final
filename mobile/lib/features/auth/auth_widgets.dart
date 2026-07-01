import 'package:flutter/material.dart';

import '../../core/theme.dart';

/// Branded header used at the top of the login/register forms.
class AuthHeader extends StatelessWidget {
  const AuthHeader({super.key, required this.title, required this.subtitle});
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 56,
          height: 56,
          decoration: BoxDecoration(
            color: AppColors.gold,
            borderRadius: BorderRadius.circular(14),
          ),
          alignment: Alignment.center,
          child: const Text('A',
              style: TextStyle(
                  color: Colors.white,
                  fontSize: 30,
                  fontWeight: FontWeight.w900)),
        ),
        const SizedBox(height: AppSpacing.lg),
        Text(title,
            style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w800)),
        const SizedBox(height: 6),
        Text(subtitle, style: const TextStyle(color: AppColors.muted)),
      ],
    );
  }
}

/// Returns the first server-side validation message for [field], if any.
String? firstError(Map<String, List<String>> errors, String field) {
  final list = errors[field];
  if (list == null || list.isEmpty) return null;
  return list.first;
}
