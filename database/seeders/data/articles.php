<?php

/**
 * Seed articles for the Performance Journal.
 *
 * Every claim here traces to a real, named, findable study, and every entry
 * ends with a `sources` block linking the actual paper on PubMed so a reader
 * can check the work. Two rules when adding to this file:
 *
 *   1. Never invent a citation. If you can't link it, don't claim it.
 *   2. Keep the caveats. Sample sizes, training status and study limitations
 *      are part of the finding, not an optional footnote.
 *
 * Block shape: ['type' => 'p'|'h2'|'callout'|'quote'|'sources', ...]
 * `sources` uses `items` (label + url) instead of `text`.
 */

return [
    [
        'title' => 'How Much Protein You Actually Need to Build Muscle',
        'category' => 'nutrition',
        'body' => [
            ['type' => 'p', 'text' => 'Protein advice tends to arrive as folklore: a gram per pound, a shake inside the magic thirty-minute window, one brand over another. The research picture is considerably duller and more useful than that, and it converges on a single number most lifters overshoot.'],

            ['type' => 'h2', 'text' => 'What the pooled evidence shows'],
            ['type' => 'p', 'text' => 'The most-cited analysis on this question is a 2018 systematic review and meta-regression in the British Journal of Sports Medicine. It pooled 49 randomised controlled trials covering 1,863 participants, all with at least six weeks of resistance training, and asked whether adding dietary protein improved the gains people made.'],
            ['type' => 'p', 'text' => 'It did. Protein supplementation produced significantly greater increases in both muscle size and strength than training alone. But the more practical finding was in the shape of the curve rather than the fact of the effect: the benefit flattened out at roughly 1.62 grams of protein per kilogram of bodyweight per day. Beyond that, the pooled data showed no further gains.'],

            ['type' => 'callout', 'text' => 'For an 80 kg lifter, roughly 1.6 g/kg works out to about 130 g of protein a day. The confidence interval around that plateau ran higher, so eating somewhat more is a reasonable hedge -- it just is not doing the work you might think it is.'],

            ['type' => 'h2', 'text' => 'What mattered less than expected'],
            ['type' => 'p', 'text' => 'In the same pooled analysis, total daily protein intake mattered more than either the timing of intake or the source of the protein. That is worth sitting with, because timing and source are where most of the marketing energy goes. Hitting your daily total with ordinary food is doing the overwhelming majority of the job.'],

            ['type' => 'h2', 'text' => 'The honest caveats'],
            ['type' => 'p', 'text' => 'A plateau in pooled averages is not a ceiling for every individual. The trials were mostly in healthy adults, they varied in training status, and they studied supplementation on top of habitual diets rather than tightly controlled total intakes. People in a calorie deficit, older adults, and very lean athletes all have reasonable arguments for aiming higher.'],
            ['type' => 'p', 'text' => 'What the evidence does not support is the belief that the difference between 1.6 and 3.0 g/kg is where your results are hiding. If your training volume and consistency are unsettled, protein is not your limiting factor.'],

            ['type' => 'sources', 'items' => [
                ['label' => 'Morton RW, et al. (2018). A systematic review, meta-analysis and meta-regression of the effect of protein supplementation on resistance training-induced gains in muscle mass and strength in healthy adults. British Journal of Sports Medicine, 52(6), 376-384.', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/28698222/'],
            ]],
        ],
    ],

    [
        'title' => 'How Many Sets Per Muscle Per Week?',
        'category' => 'training_theory',
        'body' => [
            ['type' => 'p', 'text' => 'Of all the training variables people argue about, weekly volume has the clearest dose-response evidence behind it. It is also the one most commonly gotten wrong in both directions -- three half-hearted sets on one end, twenty junk sets on the other.'],

            ['type' => 'h2', 'text' => 'The dose-response finding'],
            ['type' => 'p', 'text' => 'A 2017 meta-analysis in the Journal of Sports Sciences aggregated 15 controlled trials in which training volume was deliberately manipulated and muscle growth was measured. It found a graded relationship: more weekly sets produced more hypertrophy, with each additional weekly set associated with roughly 0.37% more muscle growth.'],
            ['type' => 'p', 'text' => 'Grouped into bands, the pattern was consistent. Fewer than five weekly sets per muscle produced growth but was not optimal. Five to nine sets did better. Ten or more sets produced the best results in the analysis -- which is where the familiar "ten sets per muscle per week" benchmark comes from.'],

            ['type' => 'callout', 'text' => 'Count sets per muscle, not per exercise. A row trains your back and your biceps; a bench press trains chest, front delts and triceps. Most people undercount their arms and overcount their chest.'],

            ['type' => 'h2', 'text' => 'Where the number stops being useful'],
            ['type' => 'p', 'text' => 'A linear trend inside the studied range is not a promise that the line continues forever. The analysis could not establish an upper limit, and the trials skewed toward untrained and moderately trained lifters, who respond to almost anything. Extrapolating to thirty sets a week because the slope was positive at twelve is not what the data says.'],
            ['type' => 'p', 'text' => 'The practical reading: if you are training a muscle fewer than five hard sets a week and wondering why it is not growing, you have found your answer. If you are already at fifteen, adding more is unlikely to be the lever that matters.'],

            ['type' => 'sources' , 'items' => [
                ['label' => 'Schoenfeld BJ, Ogborn D, Krieger JW (2017). Dose-response relationship between weekly resistance training volume and increases in muscle mass: A systematic review and meta-analysis. Journal of Sports Sciences.', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/27433992/'],
            ]],
        ],
    ],

    [
        'title' => 'Heavy or Light? Load Matters Less Than You Think',
        'category' => 'training_theory',
        'body' => [
            ['type' => 'p', 'text' => 'The idea that muscle growth requires heavy weight, and that anything above twelve reps is "toning", has been tested directly and repeatedly. It does not hold up -- with one important exception.'],

            ['type' => 'h2', 'text' => 'Same growth, different strength'],
            ['type' => 'p', 'text' => 'A 2017 systematic review and meta-analysis in the Journal of Strength and Conditioning Research compared low-load and high-load resistance training where sets were taken to momentary muscular failure. For hypertrophy, it found no meaningful advantage to heavy loads. Sets of 25 grew muscle about as well as sets of 8.'],
            ['type' => 'p', 'text' => 'Strength was a different story. There, heavy training won clearly. Maximal strength is a skill as much as a capacity, and it is specific to the loads and positions you practise. If you want a bigger one-rep max, you have to spend time near it.'],

            ['type' => 'callout', 'text' => 'The catch: those results depend on sets being taken close to failure. A comfortable set of 20 is not equivalent to a hard set of 20, and low-load sets have to get genuinely unpleasant before they count.'],

            ['type' => 'h2', 'text' => 'What to do with this'],
            ['type' => 'p', 'text' => 'It buys you freedom rather than a new rule. If your elbows dislike heavy pressing, grow your chest with higher reps. If a machine lets you push closer to failure safely than a barbell does, use the machine. Choose loads you can control through a full range, and keep proximity to failure honest.'],
            ['type' => 'p', 'text' => 'Then, if strength itself is a goal, keep some heavy work in the programme for that specific reason -- not because your muscles require it, but because your nervous system does.'],

            ['type' => 'sources', 'items' => [
                ['label' => 'Schoenfeld BJ, Grgic J, Ogborn D, Krieger JW (2017). Strength and hypertrophy adaptations between low- vs. high-load resistance training: A systematic review and meta-analysis. Journal of Strength and Conditioning Research.', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/28834797/'],
            ]],
        ],
    ],

    [
        'title' => 'Rest Longer Between Sets Than You Think',
        'category' => 'recovery',
        'body' => [
            ['type' => 'p', 'text' => 'Short rest periods have a persistent reputation for building muscle, on the logic that the burn and the pump signal growth. A controlled trial designed to test exactly that found the opposite.'],

            ['type' => 'h2', 'text' => 'One minute versus three'],
            ['type' => 'p', 'text' => 'In a 2016 study in the Journal of Strength and Conditioning Research, 21 resistance-trained men were randomly assigned to rest either one minute or three minutes between sets. Everything else was held constant: eight weeks, three full-body sessions a week, three sets of 8-12 repetition maximum across seven exercises.'],
            ['type' => 'p', 'text' => 'The three-minute group gained significantly more strength in both squat and bench press, and showed greater muscle thickness in the quadriceps. Resting longer was better on both counts.'],

            ['type' => 'p', 'text' => 'The mechanism is unglamorous. With only a minute of rest, you cannot maintain load or reps across sets -- set three becomes a shadow of set one. Total work done drops, and total work is a large part of what drives adaptation. The burn was never the signal.'],

            ['type' => 'callout', 'text' => 'A reasonable default: two to three minutes on compound lifts, sixty to ninety seconds on isolation work where fatigue is local and recovery is faster.'],

            ['type' => 'h2', 'text' => 'The caveats worth keeping'],
            ['type' => 'p', 'text' => 'This is one trial with 21 participants, not a literature. It also carries an obvious cost: three-minute rests make sessions considerably longer, and a shorter workout you actually complete beats a longer one you skip. If time is the binding constraint, pairing non-competing exercises is a better answer than rushing every set.'],

            ['type' => 'sources', 'items' => [
                ['label' => 'Schoenfeld BJ, et al. (2016). Longer interset rest periods enhance muscle strength and hypertrophy in resistance-trained men. Journal of Strength and Conditioning Research, 30(7), 1805-1812.', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/26605807/'],
            ]],
        ],
    ],

    [
        'title' => 'Sleep Is a Training Variable',
        'category' => 'recovery',
        'body' => [
            ['type' => 'p', 'text' => 'Sleep gets filed under general wellness, somewhere near drinking water. The clearest study on the subject treated it as a performance intervention instead, and the effect sizes were larger than most supplements can claim.'],

            ['type' => 'h2', 'text' => 'The Stanford basketball study'],
            ['type' => 'p', 'text' => 'Researchers at Stanford followed eleven men from the university varsity basketball team. Players kept their normal sleep schedule for a two-to-four week baseline, then extended their sleep for five to seven weeks, aiming for ten hours in bed per night.'],
            ['type' => 'p', 'text' => 'Timed sprints dropped from 16.2 to 15.5 seconds. Free-throw accuracy improved by 9 percent, and three-point accuracy by 9.2 percent. Reaction time and daytime sleepiness both improved, and mood scores moved in the right direction on both vigour and fatigue.'],

            ['type' => 'quote', 'text' => 'A nine percent improvement in shooting accuracy is the kind of number that changes a season. It came from going to bed earlier.'],

            ['type' => 'h2', 'text' => 'Read it carefully, though'],
            ['type' => 'p', 'text' => 'Eleven athletes, no control group, and a design where players knew they were being studied -- this is a signal, not proof. It also measured basketball skill, not squat strength, so applying it to lifting is an inference rather than a finding.'],
            ['type' => 'p', 'text' => 'What makes it worth acting on anyway is the direction and the cost. The intervention was free, had no downside, and the baseline it improved on was ordinary student sleep -- which is to say, probably yours.'],

            ['type' => 'sources', 'items' => [
                ['label' => 'Mah CD, Mah KE, Kezirian EJ, Dement WC (2011). The effects of sleep extension on the athletic performance of collegiate basketball players. SLEEP, 34(7), 943-950.', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/21731144/'],
            ]],
        ],
    ],

    [
        'title' => 'Train by Feel: Reps in Reserve and Autoregulation',
        'category' => 'mindset',
        'body' => [
            ['type' => 'p', 'text' => 'A programme written six weeks ago does not know you slept badly, skipped lunch, or feel unusually strong today. Autoregulation is the practice of letting the session adjust to the person who showed up for it.'],

            ['type' => 'h2', 'text' => 'Percentages are not the only option'],
            ['type' => 'p', 'text' => 'The traditional approach prescribes load as a percentage of your one-rep max. The alternatives prescribe effort instead: reps in reserve, expressed as a rating of perceived exertion, or measured bar velocity using a sensor.'],
            ['type' => 'p', 'text' => 'A 2022 systematic review and meta-analysis compared subjective autoregulation (reps-in-reserve based RPE) against objective autoregulation (velocity-based training) and found broadly similar strength improvements between them. A more recent network meta-analysis ranked autoregulated approaches above percentage-based prescription for developing maximal strength, with autoregulating progressive resistance exercise coming out on top.'],

            ['type' => 'callout', 'text' => 'Reps in reserve, in practice: RIR 2 means you could have done two more good reps. Most hypertrophy work lives around RIR 1-3. RIR 0 is failure -- useful occasionally, expensive to recover from as a default.'],

            ['type' => 'h2', 'text' => 'Why this is filed under mindset'],
            ['type' => 'p', 'text' => 'Autoregulation asks you to give up the reassurance of a fixed number and make a judgement call mid-set. That is a genuinely uncomfortable trade, and it is where most people abandon it -- pushing through a prescribed weight on a bad day feels more disciplined than adjusting.'],
            ['type' => 'p', 'text' => 'The accuracy caveat is real: estimating reps in reserve is a skill, and newer lifters consistently think they are closer to failure than they are. It improves with practice, and taking the occasional set to true failure calibrates the scale. Until then, expect your RIR 2 to be somebody else\'s RIR 5.'],

            ['type' => 'sources', 'items' => [
                ['label' => 'Greig L, et al. (2022). The effect of load and volume autoregulation on muscular strength and hypertrophy: A systematic review and meta-analysis. Sports Medicine - Open.', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/35038063/'],
                ['label' => 'Autoregulated resistance training for maximal strength enhancement: A systematic review and network meta-analysis (2025).', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/40791980/'],
            ]],
        ],
    ],
];
