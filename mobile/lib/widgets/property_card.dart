import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/theme.dart';
import '../models/property.dart';
import '../providers/favorites_provider.dart';

/// A premium property card used in carousels and list/grid views.
///
/// [horizontal] = true renders a fixed-width card suitable for a horizontal
/// carousel; false renders a full-width card for vertical lists.
class PropertyCard extends ConsumerWidget {
  const PropertyCard({
    super.key,
    required this.property,
    required this.onTap,
    this.horizontal = false,
    this.width = 260,
  });

  final Property property;
  final VoidCallback onTap;
  final bool horizontal;
  final double width;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isFav =
        ref.watch(favoritesControllerProvider).contains(property.id);

    final card = Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadii.md),
        border: Border.all(color: AppColors.border),
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _image(context, ref, isFav),
          Padding(
            padding: const EdgeInsets.all(AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  property.price.display,
                  style: const TextStyle(
                    color: AppColors.goldDark,
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  property.title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: AppColors.ink,
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    const Icon(Icons.location_on_outlined,
                        size: 14, color: AppColors.muted),
                    const SizedBox(width: 2),
                    Expanded(
                      child: Text(
                        property.location.city.name.isNotEmpty
                            ? property.location.city.name
                            : property.location.address,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                            color: AppColors.muted, fontSize: 13),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                _specsRow(),
              ],
            ),
          ),
        ],
      ),
    );

    final tappable = InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppRadii.md),
      child: card,
    );

    return horizontal ? SizedBox(width: width, child: tappable) : tappable;
  }

  Widget _image(BuildContext context, WidgetRef ref, bool isFav) {
    final url = property.displayImage;
    return Stack(
      children: [
        AspectRatio(
          aspectRatio: 16 / 10,
          child: url == null
              ? Container(
                  color: AppColors.border,
                  child: const Icon(Icons.home_outlined,
                      color: AppColors.muted, size: 40),
                )
              : CachedNetworkImage(
                  imageUrl: url,
                  fit: BoxFit.cover,
                  placeholder: (c, _) => Container(color: AppColors.border),
                  errorWidget: (c, _, __) => Container(
                    color: AppColors.border,
                    child: const Icon(Icons.broken_image_outlined,
                        color: AppColors.muted),
                  ),
                ),
        ),
        // Transaction-type & flag badges.
        Positioned(
          left: AppSpacing.sm,
          top: AppSpacing.sm,
          child: Row(
            children: [
              _badge(
                property.isRent ? 'For Rent' : 'For Sale',
                bg: AppColors.ink,
              ),
              if (property.isExclusive) ...[
                const SizedBox(width: 6),
                _badge('Exclusive', bg: AppColors.gold),
              ] else if (property.isFeatured) ...[
                const SizedBox(width: 6),
                _badge('Featured', bg: AppColors.gold),
              ],
            ],
          ),
        ),
        // Favorite toggle.
        Positioned(
          right: AppSpacing.sm,
          top: AppSpacing.sm,
          child: Material(
            color: Colors.white,
            shape: const CircleBorder(),
            child: InkWell(
              customBorder: const CircleBorder(),
              onTap: () =>
                  ref.read(favoritesControllerProvider.notifier).toggle(property),
              child: Padding(
                padding: const EdgeInsets.all(6),
                child: Icon(
                  isFav ? Icons.favorite : Icons.favorite_border,
                  size: 18,
                  color: isFav ? AppColors.danger : AppColors.ink,
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _specsRow() {
    final s = property.specs;
    final chips = <Widget>[];
    if (s.bedrooms != null) {
      chips.add(_spec(Icons.king_bed_outlined, '${s.bedrooms}'));
    }
    if (s.bathrooms != null) {
      chips.add(_spec(Icons.bathtub_outlined, '${s.bathrooms}'));
    }
    if (s.area != null) {
      chips.add(_spec(Icons.straighten,
          '${s.area!.toStringAsFixed(0)} ${s.areaUnit ?? ''}'.trim()));
    }
    if (chips.isEmpty) return const SizedBox.shrink();
    return Wrap(spacing: 14, runSpacing: 6, children: chips);
  }

  Widget _spec(IconData icon, String label) => Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: AppColors.inkSoft),
          const SizedBox(width: 4),
          Text(label,
              style: const TextStyle(color: AppColors.inkSoft, fontSize: 13)),
        ],
      );

  Widget _badge(String text, {required Color bg}) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(6),
        ),
        child: Text(
          text,
          style: const TextStyle(
              color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600),
        ),
      );
}
