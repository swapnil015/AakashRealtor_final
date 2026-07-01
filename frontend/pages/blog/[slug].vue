<script setup lang="ts">
const route = useRoute()
const api = useApi()
const config = useRuntimeConfig()

const { data, error } = await useAsyncData(
  () => `blog-${route.params.slug}`,
  () => api.get<any>(`/blogs/${route.params.slug}`),
)
if (error.value) throw createError({ statusCode: 404, statusMessage: 'Article not found', fatal: true })

const blog = computed(() => data.value!.data)

useSeoMeta({
  title: () => blog.value.title,
  description: () => blog.value.excerpt,
  ogTitle: () => blog.value.title,
  ogImage: () => blog.value.cover_image,
  ogType: 'article' as any,
})
</script>

<template>
  <article class="container-px max-w-3xl py-28">
    <NuxtLink to="/blogs" class="text-sm font-semibold text-gold-hover">← All articles</NuxtLink>
    <h1 class="mt-4 font-display text-5xl font-semibold leading-tight">{{ blog.title }}</h1>
    <p v-if="blog.author" class="mt-3 text-muted">By {{ blog.author.name }}</p>
    <img v-if="blog.cover_image" :src="blog.cover_image" :alt="blog.title" class="mt-8 w-full rounded-2xl" />
    <div class="prose mt-8 max-w-none whitespace-pre-line leading-relaxed text-ink" v-html="blog.body" />
  </article>
</template>
