import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api_exception.dart';
import '../../core/theme.dart';
import '../../models/category.dart';
import '../../models/city.dart';
import '../../providers/detail_provider.dart';
import '../../providers/providers.dart';
import '../../providers/taxonomy_provider.dart';
import '../../router/app_router.dart';
import '../../widgets/common.dart';

/// Multi-step "Post a property" flow:
///  1. Basics — title, transaction type, category, description
///  2. Details — city, address, price, bedrooms, bathrooms, area
///  3. Photos — camera/gallery upload (multipart)
///
/// On submit, POSTs to /properties via [PropertyService.create].
class PostPropertyScreen extends ConsumerStatefulWidget {
  const PostPropertyScreen({super.key});

  @override
  ConsumerState<PostPropertyScreen> createState() => _PostPropertyScreenState();
}

class _PostPropertyScreenState extends ConsumerState<PostPropertyScreen> {
  int _step = 0;
  bool _submitting = false;

  // Step 1
  final _title = TextEditingController();
  final _description = TextEditingController();
  String _transaction = 'buy';
  String? _categorySlug;
  int? _categoryId;

  // Step 2
  String? _cityId;
  final _address = TextEditingController();
  final _price = TextEditingController();
  final _bedrooms = TextEditingController();
  final _bathrooms = TextEditingController();
  final _area = TextEditingController();

  // Step 3
  final List<XFile> _images = [];
  final _picker = ImagePicker();

  Map<String, List<String>> _fieldErrors = {};

  @override
  void dispose() {
    _title.dispose();
    _description.dispose();
    _address.dispose();
    _price.dispose();
    _bedrooms.dispose();
    _bathrooms.dispose();
    _area.dispose();
    super.dispose();
  }

  // ---- Image picking ----
  Future<void> _pickFromGallery() async {
    final picked = await _picker.pickMultiImage(imageQuality: 80);
    if (picked.isNotEmpty) setState(() => _images.addAll(picked));
  }

  Future<void> _pickFromCamera() async {
    final picked =
        await _picker.pickImage(source: ImageSource.camera, imageQuality: 80);
    if (picked != null) setState(() => _images.add(picked));
  }

  bool _validateStep(int step) {
    switch (step) {
      case 0:
        return _title.text.trim().isNotEmpty && _categoryId != null;
      case 1:
        return _cityId != null && _price.text.trim().isNotEmpty;
      case 2:
        return _images.isNotEmpty;
    }
    return true;
  }

  Future<void> _submit() async {
    if (!_validateStep(2)) {
      _toast('Please add at least one photo.');
      return;
    }
    setState(() {
      _submitting = true;
      _fieldErrors = {};
    });
    try {
      num? toNum(String s) => s.trim().isEmpty ? null : num.tryParse(s.trim());
      final created = await ref.read(propertyServiceProvider).create(
        fields: {
          'title': _title.text.trim(),
          'description': _description.text.trim(),
          'transaction_type': _transaction,
          'category_id': _categoryId,
          'city_id': _cityId,
          'address': _address.text.trim(),
          'price': toNum(_price.text),
          'bedrooms': toNum(_bedrooms.text),
          'bathrooms': toNum(_bathrooms.text),
          'area': toNum(_area.text),
        },
        imagePaths: _images.map((x) => x.path).toList(),
      );
      // Refresh the user's listings so the new one shows immediately.
      ref.invalidate(myPropertiesProvider);
      if (!mounted) return;
      _toast('Property posted successfully.', success: true);
      context.go(Routes.propertyDetail(created.slug));
    } on ApiException catch (e) {
      setState(() => _fieldErrors = e.errors);
      _toast(e.message);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  void _toast(String msg, {bool success = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor: success ? AppColors.success : AppColors.danger,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final cities = ref.watch(citiesProvider);
    final categories = ref.watch(categoriesProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Post a property')),
      body: Stepper(
        currentStep: _step,
        type: StepperType.horizontal,
        onStepTapped: (s) => setState(() => _step = s),
        onStepContinue: () {
          if (!_validateStep(_step)) {
            _toast('Please complete the required fields.');
            return;
          }
          if (_step < 2) {
            setState(() => _step += 1);
          } else {
            _submit();
          }
        },
        onStepCancel: _step == 0 ? null : () => setState(() => _step -= 1),
        controlsBuilder: (context, details) {
          final isLast = _step == 2;
          return Padding(
            padding: const EdgeInsets.only(top: AppSpacing.md),
            child: Row(
              children: [
                Expanded(
                  child: ElevatedButton(
                    onPressed: _submitting ? null : details.onStepContinue,
                    child: _submitting && isLast
                        ? const SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(
                                strokeWidth: 2, color: Colors.white))
                        : Text(isLast ? 'Submit listing' : 'Continue'),
                  ),
                ),
                if (_step > 0) ...[
                  const SizedBox(width: AppSpacing.sm),
                  Expanded(
                    child: OutlinedButton(
                      onPressed: details.onStepCancel,
                      child: const Text('Back'),
                    ),
                  ),
                ],
              ],
            ),
          );
        },
        steps: [
          Step(
            title: const Text('Basics'),
            isActive: _step >= 0,
            content: _basicsStep(categories),
          ),
          Step(
            title: const Text('Details'),
            isActive: _step >= 1,
            content: _detailsStep(cities),
          ),
          Step(
            title: const Text('Photos'),
            isActive: _step >= 2,
            content: _photosStep(),
          ),
        ],
      ),
    );
  }

  // ---- Step 1 ----
  Widget _basicsStep(AsyncValue<List<Category>> categories) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        TextField(
          controller: _title,
          decoration: InputDecoration(
            labelText: 'Title *',
            errorText: _err('title'),
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        const Text('Transaction type *'),
        const SizedBox(height: 6),
        SegmentedButton<String>(
          segments: const [
            ButtonSegment(value: 'buy', label: Text('Sell')),
            ButtonSegment(value: 'rent', label: Text('Rent')),
          ],
          selected: {_transaction},
          onSelectionChanged: (s) => setState(() => _transaction = s.first),
        ),
        const SizedBox(height: AppSpacing.md),
        const Text('Category *'),
        const SizedBox(height: 6),
        categories.when(
          loading: () => const LinearProgressIndicator(color: AppColors.gold),
          error: (_, __) => const Text('Could not load categories'),
          data: (list) => Wrap(
            spacing: 8,
            runSpacing: 8,
            children: list
                .map((c) => ChoiceChip(
                      label: Text(c.name),
                      selected: _categorySlug == c.slug,
                      onSelected: (_) => setState(() {
                        _categorySlug = c.slug;
                        _categoryId = c.id;
                      }),
                    ))
                .toList(),
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        TextField(
          controller: _description,
          maxLines: 4,
          decoration: const InputDecoration(labelText: 'Description'),
        ),
      ],
    );
  }

  // ---- Step 2 ----
  Widget _detailsStep(AsyncValue<List<City>> cities) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        cities.when(
          loading: () => const LinearProgressIndicator(color: AppColors.gold),
          error: (_, __) => const Text('Could not load cities'),
          data: (list) => DropdownButtonFormField<String>(
            value: _cityId,
            isExpanded: true,
            decoration: InputDecoration(
              labelText: 'City *',
              errorText: _err('city_id'),
            ),
            items: list
                .map((c) => DropdownMenuItem(
                    value: c.publicId, child: Text(c.name)))
                .toList(),
            onChanged: (v) => setState(() => _cityId = v),
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        TextField(
          controller: _address,
          decoration: InputDecoration(
            labelText: 'Address',
            errorText: _err('address'),
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        TextField(
          controller: _price,
          keyboardType: TextInputType.number,
          decoration: InputDecoration(
            labelText: 'Price *',
            prefixText: '₹ ',
            errorText: _err('price'),
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _bedrooms,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Bedrooms'),
              ),
            ),
            const SizedBox(width: AppSpacing.sm),
            Expanded(
              child: TextField(
                controller: _bathrooms,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Bathrooms'),
              ),
            ),
          ],
        ),
        const SizedBox(height: AppSpacing.md),
        TextField(
          controller: _area,
          keyboardType: TextInputType.number,
          decoration: const InputDecoration(
              labelText: 'Area', suffixText: 'sq.ft'),
        ),
      ],
    );
  }

  // ---- Step 3 ----
  Widget _photosStep() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Add photos *',
            style: TextStyle(fontWeight: FontWeight.w700)),
        const SizedBox(height: 4),
        const Text('The first photo becomes the cover image.',
            style: TextStyle(color: AppColors.muted, fontSize: 13)),
        const SizedBox(height: AppSpacing.md),
        Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: _pickFromGallery,
                icon: const Icon(Icons.photo_library_outlined),
                label: const Text('Gallery'),
              ),
            ),
            const SizedBox(width: AppSpacing.sm),
            Expanded(
              child: OutlinedButton.icon(
                onPressed: _pickFromCamera,
                icon: const Icon(Icons.photo_camera_outlined),
                label: const Text('Camera'),
              ),
            ),
          ],
        ),
        const SizedBox(height: AppSpacing.md),
        if (_images.isEmpty)
          const EmptyView(
            icon: Icons.add_a_photo_outlined,
            title: 'No photos added',
            message: 'Add at least one photo to publish your listing.',
          )
        else
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 3,
              crossAxisSpacing: 8,
              mainAxisSpacing: 8,
            ),
            itemCount: _images.length,
            itemBuilder: (_, i) => _thumb(i),
          ),
      ],
    );
  }

  Widget _thumb(int i) {
    return Stack(
      fit: StackFit.expand,
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(AppRadii.sm),
          child: Image.file(File(_images[i].path), fit: BoxFit.cover),
        ),
        if (i == 0)
          Positioned(
            left: 4,
            top: 4,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                  color: AppColors.gold,
                  borderRadius: BorderRadius.circular(4)),
              child: const Text('Cover',
                  style: TextStyle(color: Colors.white, fontSize: 10)),
            ),
          ),
        Positioned(
          right: 2,
          top: 2,
          child: GestureDetector(
            onTap: () => setState(() => _images.removeAt(i)),
            child: const CircleAvatar(
              radius: 12,
              backgroundColor: Colors.black54,
              child: Icon(Icons.close, size: 14, color: Colors.white),
            ),
          ),
        ),
      ],
    );
  }

  String? _err(String key) {
    final list = _fieldErrors[key];
    return (list == null || list.isEmpty) ? null : list.first;
  }
}
