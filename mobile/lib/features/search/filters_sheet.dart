import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme.dart';
import '../../models/category.dart';
import '../../models/city.dart';
import '../../providers/taxonomy_provider.dart';
import '../../services/property_service.dart';

/// Bottom sheet for editing [PropertyFilters]. Returns the new filters via
/// [Navigator.pop] when "Apply" is tapped, or null when dismissed.
class FiltersSheet extends ConsumerStatefulWidget {
  const FiltersSheet({super.key, required this.initial});
  final PropertyFilters initial;

  @override
  ConsumerState<FiltersSheet> createState() => _FiltersSheetState();
}

class _FiltersSheetState extends ConsumerState<FiltersSheet> {
  late String _transaction; // buy | rent
  String? _categorySlug;
  String? _cityId;
  int? _bedrooms;
  int? _bathrooms;
  late RangeValues _price;

  // Price slider bounds (display only); values sent as raw numbers.
  static const double _priceMax = 100000000; // 10 crore ceiling

  @override
  void initState() {
    super.initState();
    _transaction = widget.initial.transactionType ?? 'buy';
    _categorySlug = widget.initial.categorySlug;
    _cityId = widget.initial.cityPublicId;
    _bedrooms = widget.initial.bedrooms;
    _bathrooms = widget.initial.bathrooms;
    _price = RangeValues(
      (widget.initial.minPrice ?? 0).toDouble(),
      (widget.initial.maxPrice ?? _priceMax).toDouble(),
    );
  }

  void _apply() {
    final filters = widget.initial.copyWith(
      transactionType: _transaction,
      categorySlug: _categorySlug,
      cityPublicId: _cityId,
      bedrooms: _bedrooms,
      bathrooms: _bathrooms,
      minPrice: _price.start <= 0 ? null : _price.start.round(),
      maxPrice: _price.end >= _priceMax ? null : _price.end.round(),
    );
    Navigator.of(context).pop(filters);
  }

  void _reset() {
    setState(() {
      _transaction = 'buy';
      _categorySlug = null;
      _cityId = null;
      _bedrooms = null;
      _bathrooms = null;
      _price = const RangeValues(0, _priceMax);
    });
  }

  @override
  Widget build(BuildContext context) {
    final cities = ref.watch(citiesProvider);
    final categories = ref.watch(categoriesProvider);

    return DraggableScrollableSheet(
      initialChildSize: 0.85,
      maxChildSize: 0.95,
      minChildSize: 0.5,
      expand: false,
      builder: (context, scrollController) {
        return Column(
          children: [
            const SizedBox(height: 12),
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: AppColors.border,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(AppSpacing.md),
              child: Row(
                children: [
                  const Text('Filters',
                      style: TextStyle(
                          fontSize: 20, fontWeight: FontWeight.w800)),
                  const Spacer(),
                  TextButton(onPressed: _reset, child: const Text('Reset')),
                ],
              ),
            ),
            Expanded(
              child: ListView(
                controller: scrollController,
                padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
                children: [
                  _transactionToggle(),
                  const SizedBox(height: AppSpacing.lg),
                  _label('Category'),
                  categories.when(
                    loading: () => const _InlineLoader(),
                    error: (_, __) => const Text('Could not load categories'),
                    data: (list) => _categoryChips(list),
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  _label('City'),
                  cities.when(
                    loading: () => const _InlineLoader(),
                    error: (_, __) => const Text('Could not load cities'),
                    data: (list) => _cityDropdown(list),
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  _label('Price range'),
                  _priceSlider(),
                  const SizedBox(height: AppSpacing.lg),
                  _label('Bedrooms'),
                  _countChips(
                    selected: _bedrooms,
                    onSelect: (v) => setState(() => _bedrooms = v),
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  _label('Bathrooms'),
                  _countChips(
                    selected: _bathrooms,
                    onSelect: (v) => setState(() => _bathrooms = v),
                  ),
                  const SizedBox(height: 100),
                ],
              ),
            ),
            SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.all(AppSpacing.md),
                child: ElevatedButton(
                  onPressed: _apply,
                  child: const Text('Apply filters'),
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _label(String text) => Padding(
        padding: const EdgeInsets.only(bottom: AppSpacing.sm),
        child: Text(text,
            style:
                const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
      );

  Widget _transactionToggle() {
    Widget seg(String value, String label) {
      final selected = _transaction == value;
      return Expanded(
        child: GestureDetector(
          onTap: () => setState(() => _transaction = value),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            padding: const EdgeInsets.symmetric(vertical: 12),
            decoration: BoxDecoration(
              color: selected ? AppColors.gold : Colors.transparent,
              borderRadius: BorderRadius.circular(AppRadii.sm),
            ),
            alignment: Alignment.center,
            child: Text(
              label,
              style: TextStyle(
                color: selected ? Colors.white : AppColors.ink,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadii.md),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(children: [seg('buy', 'Buy'), seg('rent', 'Rent')]),
    );
  }

  Widget _categoryChips(List<Category> list) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        ChoiceChip(
          label: const Text('Any'),
          selected: _categorySlug == null,
          onSelected: (_) => setState(() => _categorySlug = null),
        ),
        ...list.map((c) => ChoiceChip(
              label: Text(c.name),
              selected: _categorySlug == c.slug,
              onSelected: (_) => setState(() => _categorySlug = c.slug),
            )),
      ],
    );
  }

  Widget _cityDropdown(List<City> list) {
    return DropdownButtonFormField<String?>(
      value: _cityId,
      isExpanded: true,
      decoration: const InputDecoration(hintText: 'Any city'),
      items: [
        const DropdownMenuItem<String?>(value: null, child: Text('Any city')),
        ...list.map((c) => DropdownMenuItem<String?>(
              value: c.publicId,
              child: Text(c.name),
            )),
      ],
      onChanged: (v) => setState(() => _cityId = v),
    );
  }

  Widget _priceSlider() {
    String fmt(double v) {
      if (v >= 10000000) return '${(v / 10000000).toStringAsFixed(1)} Cr';
      if (v >= 100000) return '${(v / 100000).toStringAsFixed(1)} L';
      return v.toStringAsFixed(0);
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        RangeSlider(
          values: _price,
          min: 0,
          max: _priceMax,
          divisions: 100,
          activeColor: AppColors.gold,
          labels: RangeLabels(fmt(_price.start), fmt(_price.end)),
          onChanged: (v) => setState(() => _price = v),
        ),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(fmt(_price.start),
                style: const TextStyle(color: AppColors.muted)),
            Text(
              _price.end >= _priceMax ? '${fmt(_priceMax)}+' : fmt(_price.end),
              style: const TextStyle(color: AppColors.muted),
            ),
          ],
        ),
      ],
    );
  }

  Widget _countChips({
    required int? selected,
    required ValueChanged<int?> onSelect,
  }) {
    final options = [1, 2, 3, 4, 5];
    return Wrap(
      spacing: 8,
      children: [
        ChoiceChip(
          label: const Text('Any'),
          selected: selected == null,
          onSelected: (_) => onSelect(null),
        ),
        ...options.map((n) => ChoiceChip(
              label: Text(n == 5 ? '5+' : '$n'),
              selected: selected == n,
              onSelected: (_) => onSelect(n),
            )),
      ],
    );
  }
}

class _InlineLoader extends StatelessWidget {
  const _InlineLoader();
  @override
  Widget build(BuildContext context) => const Padding(
        padding: EdgeInsets.symmetric(vertical: 8),
        child: SizedBox(
          height: 20,
          width: 20,
          child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.gold),
        ),
      );
}
