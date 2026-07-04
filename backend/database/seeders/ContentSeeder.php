<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the marketing content shown on the homepage: verified agents
 * (team members) and "Know before you buy" insight articles.
 * Idempotent — safe to re-run.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $unsplash = fn (string $id) => "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w=1200&q=80";

        // ── Verified agents ─────────────────────────────────────────────
        $team = [
            ['name' => 'Prakash Adhikari', 'position' => 'Kathmandu North · Estates', 'photo' => $unsplash('1560250097-0b93528c311a'), 'sort_order' => 1],
            ['name' => 'Sunita Maharjan', 'position' => 'Lalitpur · Heritage & Homes', 'photo' => $unsplash('1573496359142-b8d87734a5a2'), 'sort_order' => 2],
            ['name' => 'Bibek Gurung', 'position' => 'Pokhara · Lakeside', 'photo' => $unsplash('1507003211169-0a1dd7228f2d'), 'sort_order' => 3],
            ['name' => 'Anisha Shrestha', 'position' => 'Commercial & Apartments', 'photo' => $unsplash('1580489944761-15a19d654956'), 'sort_order' => 4],
        ];

        foreach ($team as $member) {
            Team::updateOrCreate(['name' => $member['name']], $member + ['socials' => []]);
        }

        // ── Insight articles ────────────────────────────────────────────
        $author = User::where('role', 'admin')->first() ?? User::first();
        if (! $author) {
            return; // no users yet — blog author FK would fail
        }

        $blogs = [
            [
                'title' => 'Kathmandu land prices: where the smart money went in 2026',
                'excerpt' => 'A data-backed look at the valley corridors that outperformed this year — and the ones that quietly stalled.',
                'cover_image' => $unsplash('1526304640581-d334cdbbf45e'),
                'published_at' => '2026-06-15 09:00:00',
            ],
            [
                'title' => 'Aana, ropani, dhur: a clear guide to Nepali land measurement',
                'excerpt' => 'Convert between hill and terai units with confidence, and know exactly what you are paying per square foot.',
                'cover_image' => $unsplash('1500382017468-9049fed747ef'),
                'published_at' => '2026-05-20 09:00:00',
            ],
            [
                'title' => 'Checking a lalpurja before you pay: 7 things to verify',
                'excerpt' => 'From ownership chains to road-access registration — the checklist our own agents run before every deal.',
                'cover_image' => $unsplash('1450101499163-c8848c66ca85'),
                'published_at' => '2026-04-10 09:00:00',
            ],
        ];

        foreach ($blogs as $blog) {
            Blog::updateOrCreate(
                ['slug' => Str::slug($blog['title'])],
                $blog + [
                    'user_id' => $author->id,
                    'body' => '<p>'.$blog['excerpt'].'</p><p>Full article coming soon — talk to our team for the detailed report.</p>',
                ]
            );
        }
    }
}
