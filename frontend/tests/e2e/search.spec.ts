import { test, expect } from '@playwright/test'

// End-to-end: search → listing → detail → inquiry.
// Assumes the Nuxt app is running with a seeded backend (see playwright.config).

test('home → search → listing shows results', async ({ page }) => {
  await page.goto('/')
  await expect(page.getByRole('heading', { name: /lives up to you/i })).toBeVisible()

  // Use the hero search.
  await page.getByRole('button', { name: 'Search' }).click()
  await expect(page).toHaveURL(/\/(buy|rent)/)
  await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
})

test('listing → detail → submit inquiry', async ({ page }) => {
  await page.goto('/buyHouse')
  const firstCard = page.locator('a[href^="/property/"]').first()
  await expect(firstCard).toBeVisible()
  await firstCard.click()

  await expect(page).toHaveURL(/\/property\//)
  await page.getByPlaceholder('Your name').fill('Test Buyer')
  await page.getByPlaceholder('Phone').fill('9812345678')
  await page.getByRole('button', { name: /send inquiry/i }).click()

  await expect(page.getByText(/inquiry sent/i)).toBeVisible({ timeout: 10000 })
})

test('post property requires auth', async ({ page }) => {
  await page.goto('/post')
  await expect(page).toHaveURL(/\/login/)
})
