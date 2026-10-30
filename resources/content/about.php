<?php

/*
| About page (CMS-1). The body is Markdown. After M4 the admin edits it at
| /admin; this file is the starting text.
|
| PLACEHOLDER: replace with your own story, team and city.
*/

$name = config('app.name');

return [

    'title' => 'About us',
    'lead' => 'We help school children learn and college students finish their research well.',
    'description' => 'Who we are: live online tuition for school children and academic guidance, editing and printing for college students.',

    'body' => <<<MD
    {$name} offers two services from one team.

    ## Tuition for classes 1 to 12

    Live online classes in small batches of up to 15 students, so every child gets attention. Parents choose the subjects, pay a monthly fee and stay informed about every class, notice and message.

    ## Academic services for college students

    Bachelors, Masters and PhD students come to us for guidance on their synopsis, editing and proofreading, formatting to their university's style, similarity checks, and printing and binding with delivery. We work on the student's own writing and help them learn to do it well; we never supply work for them to submit as their own.

    ## How we work

    - Clear prices, published on this site.
    - Parents, not children, are our contact for tuition.
    - Your files and personal details are kept private.
    - Honest advice, even when it means telling you we are not the right fit.

    Questions? [Get in touch](/contact).
    MD,

];
