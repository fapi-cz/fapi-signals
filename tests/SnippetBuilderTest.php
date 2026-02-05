<?php

namespace FapiSignalsPlugin\Tests;

use FapiSignalsPlugin\Tracking\SnippetBuilder;
use PHPUnit\Framework\TestCase;

class SnippetBuilderTest extends TestCase
{
    public function testBuildPixelSnippetsReturnsMetaScript(): void
    {
        $builder = new SnippetBuilder();
        $settings = [
            'meta_pixel_enabled' => true,
            'meta_pixel_id' => '123',
            'ga4_pixel_enabled' => false,
            'ga4_measurement_id' => '',
            'gtm_pixel_enabled' => false,
            'gtm_container_id' => '',
            'google_ads_pixel_enabled' => false,
            'google_ads_id' => '',
            'tiktok_pixel_enabled' => false,
            'tiktok_pixel_id' => '',
            'pinterest_pixel_enabled' => false,
            'pinterest_tag_id' => '',
            'linkedin_pixel_enabled' => false,
            'linkedin_partner_id' => '',
        ];
        $snippets = $builder->buildPixelSnippets($settings);
        $this->assertNotEmpty($snippets);
        $this->assertStringContainsString('fbq', $snippets[0]);
    }

    public function testBuildPixelSnippetsIncludesEventIdForMeta(): void
    {
        $builder = new SnippetBuilder();
        $settings = [
            'meta_pixel_enabled' => true,
            'meta_pixel_id' => '123',
            'ga4_pixel_enabled' => false,
            'ga4_measurement_id' => '',
            'gtm_pixel_enabled' => false,
            'gtm_container_id' => '',
            'google_ads_pixel_enabled' => false,
            'google_ads_id' => '',
            'tiktok_pixel_enabled' => false,
            'tiktok_pixel_id' => '',
            'pinterest_pixel_enabled' => false,
            'pinterest_tag_id' => '',
            'linkedin_pixel_enabled' => false,
            'linkedin_partner_id' => '',
        ];
        $snippets = $builder->buildPixelSnippets($settings, 'event-123');
        $this->assertNotEmpty($snippets);
        $this->assertStringContainsString("eventID: 'event-123'", $snippets[0]);
    }

    public function testBuildConversionSnippetEmptyWhenDisabled(): void
    {
        $builder = new SnippetBuilder();
        $settings = [
            'meta_conversion_enabled' => false,
            'ga4_conversion_enabled' => false,
            'gtm_conversion_enabled' => false,
            'google_ads_conversion_enabled' => false,
            'google_ads_id' => '',
            'tiktok_conversion_enabled' => false,
            'pinterest_conversion_enabled' => false,
            'affilbox_conversion_enabled' => false,
            'affilbox_url' => '',
            'affilbox_campaign_id' => '',
            'cj_conversion_enabled' => false,
            'cj_enterprise_id' => '',
            'cj_action_tracker_id' => '',
            'cj_cjevent_order' => '',
            'sklik_conversion_enabled' => false,
            'sklik_id' => '',
            'sklik_zbozi_id' => '',
        ];
        $snippet = $builder->buildConversionSnippet($settings);
        $this->assertSame('', $snippet);
    }

    public function testBuildConversionSnippetIncludesMeta(): void
    {
        $builder = new SnippetBuilder();
        $settings = [
            'meta_conversion_enabled' => true,
            'ga4_conversion_enabled' => false,
            'gtm_conversion_enabled' => false,
            'google_ads_conversion_enabled' => false,
            'google_ads_id' => '',
            'tiktok_conversion_enabled' => false,
            'pinterest_conversion_enabled' => false,
            'affilbox_conversion_enabled' => false,
            'affilbox_url' => '',
            'affilbox_campaign_id' => '',
            'cj_conversion_enabled' => false,
            'cj_enterprise_id' => '',
            'cj_action_tracker_id' => '',
            'cj_cjevent_order' => '',
            'sklik_conversion_enabled' => false,
            'sklik_id' => '',
            'sklik_zbozi_id' => '',
        ];
        $snippet = $builder->buildConversionSnippet($settings);
        $this->assertStringContainsString('simpleFacebookPixelTransaction', $snippet);
    }

    public function testBuildConversionSnippetIncludesGoogleAdsWithId(): void
    {
        $builder = new SnippetBuilder();
        $settings = [
            'meta_conversion_enabled' => false,
            'ga4_conversion_enabled' => false,
            'gtm_conversion_enabled' => false,
            'google_ads_conversion_enabled' => true,
            'google_ads_id' => 'AW-123',
            'tiktok_conversion_enabled' => false,
            'pinterest_conversion_enabled' => false,
            'affilbox_conversion_enabled' => false,
            'affilbox_url' => '',
            'affilbox_campaign_id' => '',
            'cj_conversion_enabled' => false,
            'cj_enterprise_id' => '',
            'cj_action_tracker_id' => '',
            'cj_cjevent_order' => '',
            'sklik_conversion_enabled' => false,
            'sklik_id' => '',
            'sklik_zbozi_id' => '',
        ];
        $snippet = $builder->buildConversionSnippet($settings);
        $this->assertStringContainsString('simpleGoogleAdsTransaction', $snippet);
        $this->assertStringContainsString('AW-123', $snippet);
    }

    public function testBuildConversionSnippetIncludesAffilbox(): void
    {
        $builder = new SnippetBuilder();
        $settings = [
            'meta_conversion_enabled' => false,
            'ga4_conversion_enabled' => false,
            'gtm_conversion_enabled' => false,
            'google_ads_conversion_enabled' => false,
            'google_ads_id' => '',
            'tiktok_conversion_enabled' => false,
            'pinterest_conversion_enabled' => false,
            'affilbox_conversion_enabled' => true,
            'affilbox_url' => 'demo.affilbox.cz',
            'affilbox_campaign_id' => '1',
            'cj_conversion_enabled' => false,
            'cj_enterprise_id' => '',
            'cj_action_tracker_id' => '',
            'cj_cjevent_order' => '',
            'sklik_conversion_enabled' => false,
            'sklik_id' => '',
            'sklik_zbozi_id' => '',
        ];
        $snippet = $builder->buildConversionSnippet($settings);
        $this->assertStringContainsString('simpleAffilboxTransaction', $snippet);
        $this->assertStringContainsString('demo.affilbox.cz', $snippet);
    }

    public function testBuildConversionSnippetIncludesCj(): void
    {
        $builder = new SnippetBuilder();
        $settings = [
            'meta_conversion_enabled' => false,
            'ga4_conversion_enabled' => false,
            'gtm_conversion_enabled' => false,
            'google_ads_conversion_enabled' => false,
            'google_ads_id' => '',
            'tiktok_conversion_enabled' => false,
            'pinterest_conversion_enabled' => false,
            'affilbox_conversion_enabled' => false,
            'affilbox_url' => '',
            'affilbox_campaign_id' => '',
            'cj_conversion_enabled' => true,
            'cj_enterprise_id' => 'ent',
            'cj_action_tracker_id' => 'act',
            'cj_cjevent_order' => 'order',
            'sklik_conversion_enabled' => false,
            'sklik_id' => '',
            'sklik_zbozi_id' => '',
        ];
        $snippet = $builder->buildConversionSnippet($settings);
        $this->assertStringContainsString('simpleCJTransaction', $snippet);
        $this->assertStringContainsString('ent', $snippet);
    }

    public function testBuildConversionSnippetIncludesSklik(): void
    {
        $builder = new SnippetBuilder();
        $settings = [
            'meta_conversion_enabled' => false,
            'ga4_conversion_enabled' => false,
            'gtm_conversion_enabled' => false,
            'google_ads_conversion_enabled' => false,
            'google_ads_id' => '',
            'tiktok_conversion_enabled' => false,
            'pinterest_conversion_enabled' => false,
            'affilbox_conversion_enabled' => false,
            'affilbox_url' => '',
            'affilbox_campaign_id' => '',
            'cj_conversion_enabled' => false,
            'cj_enterprise_id' => '',
            'cj_action_tracker_id' => '',
            'cj_cjevent_order' => '',
            'sklik_conversion_enabled' => true,
            'sklik_id' => '123',
            'sklik_zbozi_id' => '456',
        ];
        $snippet = $builder->buildConversionSnippet($settings);
        $this->assertStringContainsString('simpleSklikTransaction', $snippet);
        $this->assertStringContainsString('123', $snippet);
        $this->assertStringContainsString('456', $snippet);
    }

    public function testBuildPixelSnippetsDoesNotIncludeRewardsScript(): void
    {
        $builder = new SnippetBuilder();
        $settings = [
            'meta_pixel_enabled' => false,
            'meta_pixel_id' => '',
            'ga4_pixel_enabled' => false,
            'ga4_measurement_id' => '',
            'gtm_pixel_enabled' => false,
            'gtm_container_id' => '',
            'google_ads_pixel_enabled' => false,
            'google_ads_id' => '',
            'tiktok_pixel_enabled' => false,
            'tiktok_pixel_id' => '',
            'pinterest_pixel_enabled' => false,
            'pinterest_tag_id' => '',
            'linkedin_pixel_enabled' => false,
            'linkedin_partner_id' => '',
            'rewards_script_enabled' => true,
        ];
        $snippets = $builder->buildPixelSnippets($settings);
        $this->assertSame([], $snippets);
    }
}
