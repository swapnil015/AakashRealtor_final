import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api_exception.dart';
import '../../core/theme.dart';
import '../../providers/auth_provider.dart';
import '../../providers/providers.dart';

/// Inline inquiry form shown on the property detail screen.
///
/// Pre-fills name/email/phone from the signed-in user when available, and
/// POSTs to /inquiries via [LeadService].
class InquiryForm extends ConsumerStatefulWidget {
  const InquiryForm({super.key, required this.propertyId, required this.propertyTitle});

  final int propertyId;
  final String propertyTitle;

  @override
  ConsumerState<InquiryForm> createState() => _InquiryFormState();
}

class _InquiryFormState extends ConsumerState<InquiryForm> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();
  late final TextEditingController _message;

  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _message = TextEditingController(
      text: "I'm interested in \"${widget.propertyTitle}\". "
          'Please share more details.',
    );
    // Prefill from the authenticated user if present.
    final user = ref.read(authControllerProvider).user;
    if (user != null) {
      _name.text = user.name;
      _email.text = user.email;
      _phone.text = user.phone ?? '';
    }
  }

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _email.dispose();
    _message.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);
    try {
      final msg = await ref.read(leadServiceProvider).sendInquiry(
            propertyId: widget.propertyId,
            name: _name.text.trim(),
            phone: _phone.text.trim(),
            email: _email.text.trim().isEmpty ? null : _email.text.trim(),
            message: _message.text.trim(),
          );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(msg.isNotEmpty ? msg : 'Inquiry sent successfully.'),
          backgroundColor: AppColors.success,
        ),
      );
      _message.text = '';
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.message), backgroundColor: AppColors.danger),
      );
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Send an inquiry',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
          const SizedBox(height: AppSpacing.md),
          TextFormField(
            controller: _name,
            decoration: const InputDecoration(hintText: 'Your name'),
            validator: (v) =>
                (v == null || v.trim().isEmpty) ? 'Name is required' : null,
          ),
          const SizedBox(height: AppSpacing.sm),
          TextFormField(
            controller: _phone,
            keyboardType: TextInputType.phone,
            decoration: const InputDecoration(hintText: 'Phone number'),
            validator: (v) =>
                (v == null || v.trim().length < 7) ? 'Enter a valid phone' : null,
          ),
          const SizedBox(height: AppSpacing.sm),
          TextFormField(
            controller: _email,
            keyboardType: TextInputType.emailAddress,
            decoration: const InputDecoration(hintText: 'Email (optional)'),
          ),
          const SizedBox(height: AppSpacing.sm),
          TextFormField(
            controller: _message,
            maxLines: 4,
            decoration: const InputDecoration(hintText: 'Message'),
            validator: (v) =>
                (v == null || v.trim().isEmpty) ? 'Message is required' : null,
          ),
          const SizedBox(height: AppSpacing.md),
          ElevatedButton(
            onPressed: _submitting ? null : _submit,
            child: _submitting
                ? const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(
                        strokeWidth: 2, color: Colors.white),
                  )
                : const Text('Send inquiry'),
          ),
        ],
      ),
    );
  }
}
