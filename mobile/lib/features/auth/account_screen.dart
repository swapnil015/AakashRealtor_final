import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/config.dart';
import '../../core/theme.dart';
import '../../providers/auth_provider.dart';
import '../../router/app_router.dart';

/// Account / profile tab. Shows the signed-in user and entry points to
/// listings, inquiries, posting, and logout. Prompts for sign-in when logged
/// out.
class AccountScreen extends ConsumerWidget {
  const AccountScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Account')),
      body: ListView(
        padding: const EdgeInsets.all(AppSpacing.md),
        children: [
          if (auth.isAuthenticated) _profileHeader(ref) else _signInCard(context),
          const SizedBox(height: AppSpacing.lg),
          _tile(
            icon: Icons.add_home_outlined,
            title: 'Post a property',
            subtitle: 'List your property for sale or rent',
            onTap: () => context.push(Routes.postProperty),
          ),
          _tile(
            icon: Icons.home_work_outlined,
            title: 'My listings',
            onTap: () => context.push(Routes.myListings),
          ),
          _tile(
            icon: Icons.mark_email_read_outlined,
            title: 'My inquiries',
            onTap: () => context.push(Routes.inquiries),
          ),
          _tile(
            icon: Icons.favorite_border,
            title: 'Saved properties',
            onTap: () => context.go(Routes.favorites),
          ),
          const Divider(height: 32),
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: AppSpacing.sm),
            child: Text('About',
                style: TextStyle(
                    color: AppColors.muted, fontWeight: FontWeight.w600)),
          ),
          _tile(
            icon: Icons.info_outline,
            title: 'API endpoint',
            subtitle: AppConfig.apiBaseUrl,
            onTap: null,
          ),
          if (auth.isAuthenticated) ...[
            const SizedBox(height: AppSpacing.md),
            OutlinedButton.icon(
              onPressed: () =>
                  ref.read(authControllerProvider.notifier).logout(),
              icon: const Icon(Icons.logout, color: AppColors.danger),
              label: const Text('Log out',
                  style: TextStyle(color: AppColors.danger)),
              style: OutlinedButton.styleFrom(
                side: const BorderSide(color: AppColors.danger),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _profileHeader(WidgetRef ref) {
    final user = ref.read(authControllerProvider).user!;
    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadii.md),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 30,
            backgroundColor: AppColors.gold,
            child: Text(user.initials,
                style: const TextStyle(
                    color: Colors.white,
                    fontSize: 22,
                    fontWeight: FontWeight.w800)),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(user.name,
                    style: const TextStyle(
                        fontSize: 18, fontWeight: FontWeight.w800)),
                const SizedBox(height: 2),
                Text(user.email,
                    style: const TextStyle(color: AppColors.muted)),
                if (user.phone != null)
                  Text(user.phone!,
                      style: const TextStyle(color: AppColors.muted)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _signInCard(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.lg),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadii.md),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Welcome to Aakash Realtor',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
          const SizedBox(height: 4),
          const Text('Sign in to save homes, post listings and track inquiries.',
              style: TextStyle(color: AppColors.muted)),
          const SizedBox(height: AppSpacing.md),
          Row(
            children: [
              Expanded(
                child: ElevatedButton(
                  onPressed: () => context.push(Routes.login),
                  child: const Text('Sign in'),
                ),
              ),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: OutlinedButton(
                  onPressed: () => context.push(Routes.register),
                  child: const Text('Register'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _tile({
    required IconData icon,
    required String title,
    String? subtitle,
    VoidCallback? onTap,
  }) {
    return Card(
      margin: const EdgeInsets.only(bottom: AppSpacing.sm),
      child: ListTile(
        leading: Icon(icon, color: AppColors.gold),
        title: Text(title,
            style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: subtitle == null
            ? null
            : Text(subtitle,
                maxLines: 1, overflow: TextOverflow.ellipsis),
        trailing: onTap == null
            ? null
            : const Icon(Icons.chevron_right, color: AppColors.muted),
        onTap: onTap,
      ),
    );
  }
}
