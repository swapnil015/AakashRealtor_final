import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme.dart';
import '../../providers/detail_provider.dart';
import '../../widgets/common.dart';

/// Lists the inquiries the signed-in user has submitted (GET /my/inquiries).
///
/// The backend shape isn't strictly typed in the brief, so this renders a
/// resilient view over loosely-typed maps (property title, message, status,
/// optional agent reply).
class InquiriesScreen extends ConsumerWidget {
  const InquiriesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(myInquiriesProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('My inquiries')),
      body: async.when(
        loading: () => const LoadingView(),
        error: (e, _) => ErrorView(
          message: 'Could not load your inquiries.',
          onRetry: () => ref.refresh(myInquiriesProvider),
        ),
        data: (items) => items.isEmpty
            ? const EmptyView(
                icon: Icons.mark_email_read_outlined,
                title: 'No inquiries yet',
                message:
                    'When you contact agents about a property, your messages '
                    'appear here.',
              )
            : RefreshIndicator(
                color: AppColors.gold,
                onRefresh: () async => ref.refresh(myInquiriesProvider.future),
                child: ListView.separated(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  itemCount: items.length,
                  separatorBuilder: (_, __) =>
                      const SizedBox(height: AppSpacing.sm),
                  itemBuilder: (_, i) => _InquiryTile(data: items[i]),
                ),
              ),
      ),
    );
  }
}

class _InquiryTile extends StatelessWidget {
  const _InquiryTile({required this.data});
  final Map<String, dynamic> data;

  String _str(List<String> keys, [String fallback = '']) {
    for (final k in keys) {
      final v = data[k];
      if (v is String && v.isNotEmpty) return v;
      if (v is Map && v['title'] is String) return v['title'] as String;
    }
    return fallback;
  }

  @override
  Widget build(BuildContext context) {
    final title = _str(['property_title', 'property'], 'Property inquiry');
    final message = _str(['message', 'body']);
    final status = _str(['status'], 'sent');
    final reply = _str(['reply', 'agent_reply']);
    final createdAt = _str(['created_at', 'date']);

    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadii.md),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(title,
                    style: const TextStyle(
                        fontWeight: FontWeight.w700, fontSize: 15)),
              ),
              _statusChip(status),
            ],
          ),
          if (message.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(message,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: AppColors.inkSoft)),
          ],
          if (reply.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.sm),
            Container(
              padding: const EdgeInsets.all(AppSpacing.sm),
              decoration: BoxDecoration(
                color: AppColors.bg,
                borderRadius: BorderRadius.circular(AppRadii.sm),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(Icons.reply, size: 16, color: AppColors.gold),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(reply,
                        style: const TextStyle(color: AppColors.inkSoft)),
                  ),
                ],
              ),
            ),
          ],
          if (createdAt.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(createdAt,
                style: const TextStyle(color: AppColors.muted, fontSize: 12)),
          ],
        ],
      ),
    );
  }

  Widget _statusChip(String status) {
    final replied = status.toLowerCase().contains('repl');
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: replied ? AppColors.success : AppColors.muted,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(status,
          style: const TextStyle(
              color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600)),
    );
  }
}
