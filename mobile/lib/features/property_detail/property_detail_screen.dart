import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/theme.dart';
import '../../models/property.dart';
import '../../providers/detail_provider.dart';
import '../../providers/favorites_provider.dart';
import '../../router/app_router.dart';
import '../../widgets/common.dart';
import '../../widgets/property_card.dart';
import 'inquiry_form.dart';

/// Full property detail: image gallery, specs, amenities, map, agent contact,
/// inquiry form, and similar listings.
class PropertyDetailScreen extends ConsumerWidget {
  const PropertyDetailScreen({super.key, required this.slug});
  final String slug;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(propertyDetailProvider(slug));

    return Scaffold(
      body: async.when(
        loading: () => const LoadingView(),
        error: (e, _) => Scaffold(
          appBar: AppBar(),
          body: ErrorView(
            message: 'Could not load this property.',
            onRetry: () => ref.refresh(propertyDetailProvider(slug)),
          ),
        ),
        data: (property) => _DetailBody(property: property),
      ),
    );
  }
}

class _DetailBody extends ConsumerStatefulWidget {
  const _DetailBody({required this.property});
  final Property property;

  @override
  ConsumerState<_DetailBody> createState() => _DetailBodyState();
}

class _DetailBodyState extends ConsumerState<_DetailBody> {
  final _pageController = PageController();
  int _imageIndex = 0;

  Property get p => widget.property;

  Future<void> _launch(String scheme, String path) async {
    final uri = Uri(scheme: scheme, path: path);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Could not open $scheme.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isFav = ref.watch(favoritesControllerProvider).contains(p.id);

    return CustomScrollView(
      slivers: [
        _galleryAppBar(isFav),
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.all(AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _priceAndBadges(),
                const SizedBox(height: AppSpacing.sm),
                Text(p.title,
                    style: const TextStyle(
                        fontSize: 22, fontWeight: FontWeight.w800)),
                const SizedBox(height: 6),
                Row(
                  children: [
                    const Icon(Icons.location_on_outlined,
                        size: 18, color: AppColors.muted),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        [p.location.address, p.location.city.name]
                            .where((e) => e.isNotEmpty)
                            .join(', '),
                        style: const TextStyle(color: AppColors.muted),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: AppSpacing.lg),
                _specsGrid(),
                if (p.description != null && p.description!.isNotEmpty) ...[
                  const SizedBox(height: AppSpacing.lg),
                  _sectionTitle('Description'),
                  const SizedBox(height: AppSpacing.sm),
                  Text(p.description!,
                      style: const TextStyle(height: 1.6, color: AppColors.inkSoft)),
                ],
                if (p.amenities.isNotEmpty) ...[
                  const SizedBox(height: AppSpacing.lg),
                  _sectionTitle('Amenities'),
                  const SizedBox(height: AppSpacing.sm),
                  _amenities(),
                ],
                if (p.location.hasCoordinates) ...[
                  const SizedBox(height: AppSpacing.lg),
                  _sectionTitle('Location'),
                  const SizedBox(height: AppSpacing.sm),
                  _map(),
                ],
                if (p.agent != null) ...[
                  const SizedBox(height: AppSpacing.lg),
                  _sectionTitle('Agent'),
                  const SizedBox(height: AppSpacing.sm),
                  _agentCard(),
                ],
                const SizedBox(height: AppSpacing.lg),
                const Divider(),
                const SizedBox(height: AppSpacing.md),
                InquiryForm(propertyId: p.id, propertyTitle: p.title),
              ],
            ),
          ),
        ),
        if (p.similar.isNotEmpty) _similar(),
        const SliverToBoxAdapter(child: SizedBox(height: 24)),
      ],
    );
  }

  // ---- Gallery app bar ----
  Widget _galleryAppBar(bool isFav) {
    final urls = p.galleryUrls;
    return SliverAppBar(
      expandedHeight: 300,
      pinned: true,
      backgroundColor: AppColors.ink,
      foregroundColor: Colors.white,
      leading: IconButton(
        icon: const CircleAvatar(
          backgroundColor: Colors.black38,
          child: Icon(Icons.arrow_back, color: Colors.white),
        ),
        onPressed: () => context.pop(),
      ),
      actions: [
        IconButton(
          icon: CircleAvatar(
            backgroundColor: Colors.black38,
            child: Icon(isFav ? Icons.favorite : Icons.favorite_border,
                color: isFav ? AppColors.danger : Colors.white),
          ),
          onPressed: () =>
              ref.read(favoritesControllerProvider.notifier).toggle(p),
        ),
        const SizedBox(width: 8),
      ],
      flexibleSpace: FlexibleSpaceBar(
        background: urls.isEmpty
            ? Container(
                color: AppColors.border,
                child: const Icon(Icons.home_outlined,
                    size: 64, color: AppColors.muted))
            : Stack(
                fit: StackFit.expand,
                children: [
                  PageView.builder(
                    controller: _pageController,
                    onPageChanged: (i) => setState(() => _imageIndex = i),
                    itemCount: urls.length,
                    itemBuilder: (_, i) => CachedNetworkImage(
                      imageUrl: urls[i],
                      fit: BoxFit.cover,
                      placeholder: (c, _) => Container(color: AppColors.border),
                      errorWidget: (c, _, __) =>
                          Container(color: AppColors.border),
                    ),
                  ),
                  Positioned(
                    bottom: 12,
                    left: 0,
                    right: 0,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: List.generate(
                        urls.length,
                        (i) => AnimatedContainer(
                          duration: const Duration(milliseconds: 200),
                          margin: const EdgeInsets.symmetric(horizontal: 3),
                          width: i == _imageIndex ? 18 : 6,
                          height: 6,
                          decoration: BoxDecoration(
                            color: i == _imageIndex
                                ? AppColors.gold
                                : Colors.white70,
                            borderRadius: BorderRadius.circular(3),
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
      ),
    );
  }

  Widget _priceAndBadges() {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Text(p.price.display,
            style: const TextStyle(
                fontSize: 26,
                fontWeight: FontWeight.w900,
                color: AppColors.goldDark)),
        if (p.price.unit.isNotEmpty)
          Padding(
            padding: const EdgeInsets.only(left: 6, bottom: 3),
            child: Text(p.price.unit,
                style: const TextStyle(color: AppColors.muted)),
          ),
        const Spacer(),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
          decoration: BoxDecoration(
            color: AppColors.ink,
            borderRadius: BorderRadius.circular(6),
          ),
          child: Text(p.isRent ? 'For Rent' : 'For Sale',
              style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w600,
                  fontSize: 12)),
        ),
      ],
    );
  }

  Widget _specsGrid() {
    final s = p.specs;
    final items = <_SpecItem>[
      if (s.bedrooms != null)
        _SpecItem(Icons.king_bed_outlined, '${s.bedrooms}', 'Bedrooms'),
      if (s.bathrooms != null)
        _SpecItem(Icons.bathtub_outlined, '${s.bathrooms}', 'Bathrooms'),
      if (s.area != null)
        _SpecItem(Icons.straighten, s.area!.toStringAsFixed(0),
            s.areaUnit ?? 'Area'),
      if (s.parking != null)
        _SpecItem(Icons.local_parking_outlined, '${s.parking}', 'Parking'),
    ];
    if (items.isEmpty) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadii.md),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: items
            .map((e) => Column(
                  children: [
                    Icon(e.icon, color: AppColors.gold),
                    const SizedBox(height: 6),
                    Text(e.value,
                        style:
                            const TextStyle(fontWeight: FontWeight.w800)),
                    Text(e.label,
                        style: const TextStyle(
                            fontSize: 12, color: AppColors.muted)),
                  ],
                ))
            .toList(),
      ),
    );
  }

  Widget _amenities() {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: p.amenities
          .map((a) => Chip(
                avatar: const Icon(Icons.check_circle,
                    size: 16, color: AppColors.gold),
                label: Text(a.name),
                backgroundColor: AppColors.surface,
              ))
          .toList(),
    );
  }

  Widget _map() {
    final pos = LatLng(p.location.latitude!, p.location.longitude!);
    return ClipRRect(
      borderRadius: BorderRadius.circular(AppRadii.md),
      child: SizedBox(
        height: 180,
        child: GoogleMap(
          initialCameraPosition: CameraPosition(target: pos, zoom: 14),
          markers: {
            Marker(markerId: MarkerId(p.slug), position: pos),
          },
          zoomControlsEnabled: false,
          myLocationButtonEnabled: false,
          liteModeEnabled: true, // lightweight static-ish map on Android
        ),
      ),
    );
  }

  Widget _agentCard() {
    final agent = p.agent!;
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadii.md),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 26,
            backgroundColor: AppColors.gold,
            backgroundImage: agent.avatarUrl != null
                ? CachedNetworkImageProvider(agent.avatarUrl!)
                : null,
            child: agent.avatarUrl == null
                ? Text(
                    agent.name.isNotEmpty ? agent.name[0].toUpperCase() : '?',
                    style: const TextStyle(
                        color: Colors.white, fontWeight: FontWeight.w800))
                : null,
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(agent.name,
                    style: const TextStyle(fontWeight: FontWeight.w700)),
                const Text('Listing agent',
                    style: TextStyle(color: AppColors.muted, fontSize: 13)),
              ],
            ),
          ),
          if (agent.phone != null) ...[
            _circleAction(
                Icons.call, () => _launch('tel', agent.phone!)),
            const SizedBox(width: 8),
            _circleAction(Icons.chat_outlined,
                () => _launch('sms', agent.phone!)),
          ],
        ],
      ),
    );
  }

  Widget _circleAction(IconData icon, VoidCallback onTap) => Material(
        color: AppColors.ink,
        shape: const CircleBorder(),
        child: InkWell(
          customBorder: const CircleBorder(),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.all(10),
            child: Icon(icon, color: Colors.white, size: 20),
          ),
        ),
      );

  Widget _similar() {
    return SliverToBoxAdapter(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Padding(
            padding: EdgeInsets.fromLTRB(
                AppSpacing.md, AppSpacing.sm, AppSpacing.md, AppSpacing.sm),
            child: Text('Similar properties',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
          ),
          SizedBox(
            height: 252,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
              itemCount: p.similar.length,
              separatorBuilder: (_, __) => const SizedBox(width: AppSpacing.md),
              itemBuilder: (_, i) {
                final sp = p.similar[i];
                return PropertyCard(
                  property: sp,
                  horizontal: true,
                  onTap: () =>
                      context.push(Routes.propertyDetail(sp.slug)),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _sectionTitle(String text) => Text(text,
      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800));
}

class _SpecItem {
  _SpecItem(this.icon, this.value, this.label);
  final IconData icon;
  final String value;
  final String label;
}
