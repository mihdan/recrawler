<?php
namespace Mihdan\ReCrawler\Enums;

enum BlockAiCrawlerEnum: string
{
	case CHATGPT_USER = 'ChatGPT-User';
	case BING_AI = 'BingAI';
	case OPENAI = 'OpenAI';
	case ANTHROPIC_AI = 'AnthropicAI';
	case JASPER_AI = 'JasperAI';
	case AI_CONTENT_DETECTOR = 'AI Content Detector';
	case AI_SEO_CRAWLER = 'AI SEO Crawler';
	case GRAMMARLY = 'Grammarly';
	case COPYSCAPE = 'Copyscape';
	case QUILLBOT = 'QuillBot';
	case WRITESONIC = 'Writesonic';
	case HYPOTENUSE_AI = 'Hypotenuse AI';
	case COPY_AI = 'CopyAI';
	case FRASE_AI = 'Frase AI';
	case CONTENTBOT = 'ContentBot';
	case DEEPAI = 'DeepAI';
	case INFERKIT = 'Inferkit';
	case SUDOWRITE = 'Sudowrite';
	case AI_WRITER = 'AI Writer';
	case INK_EDITOR = 'INK Editor';
	case SCALENUT = 'Scalenut';
	case WRITECREAM = 'Writecream';
	case ZIMM_WRITER = 'ZimmWriter';
	case SCALENUT_BOT = 'ScalenutBot';
	case CONTENTEDGE = 'Contentedge';
	case RYTR = 'Rytr';
	case ANYWORD = 'Anyword';
	case WORDTUNE = 'Wordtune';
	case WORDAI = 'WordAI';
	case SPIN_REWRITER = 'Spin Rewriter';
	case NEURAL_TEXT = 'Neural Text';
	case WRITESCOPE = 'Writescope';
	case SIMPLIFIED_AI = 'Simplified AI';
	case TEXT_BLAZE = 'Text Blaze';
	case OPENTEXT_AI = 'OpenText AI';
	case DEEPL = 'DeepL';
	case SAPLING_AI = 'SaplingAI';
	case COPYMATIC = 'Copymatic';
	case AI_DUNGEON = 'AI Dungeon';
	case NARRATIVE_DEVICE = 'Narrative Device';
	case TEXTCORTEX = 'TextCortex';
	case AI21_LABS = 'AI21 Labs';
	case WRITER_ZEN = 'WriterZen';
	case OUTWRITE = 'Outwrite';
	case SEO_CONTENT_MACHINE = 'SEO Content Machine';
	case CRAWLQ_AI = 'CrawlQ AI';
	case SLICKWRITE = 'SlickWrite';
	case PROWRITING_AID = 'ProWritingAid';
	case HEMINGWAY_EDITOR = 'Hemingway Editor';
	case CONTENT_HARMONY = 'Content Harmony';
	case CONTENT_KING = 'Content King';
	case ROBOT_SPIDER = 'RobotSpider';
	case CONTENT_AT_SCALE = 'ContentAtScale';
	case SURFER_AI = 'Surfer AI';
	case INK_FOR_ALL = 'INKforall';
	case CLEARSCOPE = 'ClearScope';
	case MARKETMUSE = 'MarketMuse';
	case NEURAL_SEO = 'NeuralSEO';
	case CONVERSION_AI = 'Conversion AI';
	case CONTENT_SAMURAI = 'Content Samurai';
	case VIDNAMI_AI = 'Vidnami AI';
	case KAFKAI = 'Kafkai';
	case PARAPHRASER_IO = 'Paraphraser.io';
	case SPINBOT = 'Spinbot';
	case ARTICOOLO = 'Articoolo';
	case AI_ARTICLE_WRITER = 'AI Article Writer';
	case SEO_ROBOT = 'SEO Robot';
	case AI_SEARCH_ENGINE = 'AI Search Engine';
	case AUTOMATED_WRITER = 'Automated Writer';
	case SCRIPTBOOK = 'ScriptBook';
	case KEYWORD_DENSITY_AI = 'Keyword Density AI';
	case METATAG_BOT = 'MetaTagBot';
	case CONTENT_OPTIMIZER = 'Content Optimizer';
	case PAGE_ANALYZER_AI = 'Page Analyzer AI';

	public static function toArray(): array
	{
		return array_reduce(
			self::cases(),
			static fn($carry, $case) => array_merge($carry, [$case->name => $case->value]),
			[]
		);
	}
}
