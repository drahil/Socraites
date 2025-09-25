<?php

declare(strict_types=1);

namespace drahil\Socraites\Resources;

class SocratesQuotes
{
    private static array $quotes = [
        "The unexamined life is not worth living.",
        "True wisdom comes to each of us when we realize how little we understand about life, ourselves, and the world around us.",
        "By all means, marry. If you get a good wife, you'll become happy; if you get a bad one, you'll become a philosopher.",
        "I cannot teach anybody anything. I can only make them think.",
        "Strong minds discuss ideas, average minds discuss events, weak minds discuss people.",
        "To find yourself, think for yourself.",
        "Wonder is the beginning of wisdom.",
        "Be kind, for everyone you meet is fighting a hard battle.",
        "Education is the kindling of a flame, not the filling of a vessel.",
        "The only true wisdom is in knowing you know nothing.",
        "Beware the barrenness of a busy life.",
        "The secret of happiness, you see, is not found in seeking more, but in developing the capacity to enjoy less.",
        "Let him who would move the world first move himself.",
        "Know thyself.",
        "The greatest way to live with honor in this world is to be what we pretend to be.",
        "He who is not contented with what he has, would not be contented with what he would like to have.",
        "The hour of departure has arrived, and we go our separate ways, I to die, and you to live. Which of these two is better only God knows.",
        "Once made equal to man, woman becomes his superior.",
        "When the debate is lost, slander becomes the tool of the loser.",
        "Every action has its pleasures and its price.",
        "I am not an Athenian or a Greek, but a citizen of the world.",
        "The way to gain a good reputation is to endeavor to be what you desire to appear.",
        "Prefer knowledge to wealth, for the one is transitory, the other perpetual.",
        "Understanding a question is half an answer.",
        "Wisdom begins in wonder.",
        "Be slow to fall into friendship; but when thou art in, continue firm and constant.",
        "False words are not only evil in themselves, but they infect the soul with evil.",
        "The easiest and noblest way is not to be crushing others, but to be improving yourselves.",
        "Sometimes you put walls up not to keep people out, but to see who cares enough to break them down.",
        "Life contains but two tragedies. One is not to get your heart's desire; the other is to get it."
    ];

    public static function getRandomQuote(): string
    {
        return self::$quotes[array_rand(self::$quotes)];
    }
}
