export const webworkTemplateOptions = [{
  text: 'Choose a template',
  value: null,
  template: ''
},
  {
    text: 'Complex numerical',
    value: 'complex numerical',
    template: `DOCUMENT();
# Description
#   Problem_Set
#   Chapter
#   Question
# EndDescription

      loadMacros(
      "PGstandard.pl",
      "MathObjects.pl",
      "PGunion.pl",
      "weightedGrader.pl",
      );

      @ans = ();        # add more answers to array if needed
      $ans[0] = 123.456;

      sub isCorrect{
        my $s = @_[0]; #student answer
        my $c = @_[1]; #correct answer
        my $t = $c * 0.03; #tolerance

        if ($c > 0) {  # positive
          return ($s <= $c + $t) & ($s >= $c - $t);
        }
        else {        # negative
          return ($s >= $c + $t) & ($s <= $c - $t);
        }
      }

      sub checker1 {  # add more depending on @ans size
        my ($correct, $student, $ansHash) = @_;
        my $pass = isCorrect($student, $ans[0]);

        return $pass;
      }

      BEGIN_TEXT
      Text
      $BR
      \\{ans_rule(10)\\}
      END_TEXT

      $showPartialCorrectAnswers = 0;

      # Answer checking, add more depending on @ans size
      WEIGHTED_ANS(Compute(0)->cmp(checker=>~~&checker1), 0);

ENDDOCUMENT();
`
  }, {
    text: 'Multiple choice',
    value: 'multiple choice',
    template: `DOCUMENT();
# Description
#   Problem_Set
#   Chapter
#   Question
# EndDescription

    loadMacros(
    "PGstandard.pl",
    "MathObjects.pl",
    "parserRadioButtons.pl",
    );

    @mc = ();
    $mc[0] = RadioButtons(
        ["Choice 1",    # must have at least 2 choices
        "Choice 2",
        "Choice 3",
        "Choice 4"],
        "Choice 3",     # correct answer, must be one of the choices above
        labels => "ABC" );

    BEGIN_TEXT
    Question
    $BR
    \\{$mc[0] -> buttons()\\}
    END_TEXT

    foreach my $i (0..0){
      ANS($mc[$i]->cmp());
    }

    $showPartialCorrectAnswers = 0;

ENDDOCUMENT();
`
  },
  {
    text: 'Multiple correct answers',
    value: 'multiple correct answers',
    template: `DOCUMENT();
# Description
#   Problem_Set
#   Chapter
#   Question
# EndDescription

    loadMacros(
    "PGstandard.pl",
    "PGchoicemacros.pl",
    "PGcourse.pl",
    );

    $mc = new_checkbox_multiple_choice();
    $mc->qa("Question", "Correct Answer A", "Correct Answer B");  # first item is the question, the rest are correct answers
    $mc->extra("Wrong Answer A", "Wrong Answer B");               # wrong answers

    $showPartialCorrectAnswers = 0;

    BEGIN_TEXT
    \\{ $mc -> print_q() \\}
    \\{ $mc -> print_a() \\}
    END_TEXT

    install_problem_grader(~~&std_problem_grader);
    ANS( checkbox_cmp( $mc->correct_ans() ) );

ENDDOCUMENT();
`
  },
  {
    text: 'Reaction',
    value: 'reaction',
    template: `DOCUMENT();
# Description
#   Problem_Set
#   Chapter
#   Question
# EndDescription

    loadMacros(
    "PGstandard.pl",
    "PGanswermacros.pl",
    "MathObjects.pl",
    "PGunion.pl",
    "contextReaction.pl"
    );

    Context("Reaction");
    @f = ();   # add to the array
    $f[0] = Formula("2KI + Br_2 --> 2KBr + I_2");

    $showPartialCorrectAnswers = 0;

    BEGIN_TEXT
    a. Text
    $BR
    \\{ans_rule(10)\\}
    $BR $BR
    Use the following style of formatting:
    $BR
    2Cr_2O_7^-2 --> 2Cr_2O_7^-2 equals \\(2\\text{Cr}_2\\text{O}_7^{-2} \\rightarrow 2\\text{Cr}_2\\text{O}_7^{-2}\\)
    $BR $BR
    Do not add the state of matter into the answer or else it will be marked incorrect.
    END_TEXT

    foreach my $i (0..0){     # change based on array size
          ANS($f[$i]->cmp() );
    }

ENDDOCUMENT();`
  },
  {
    text: 'Simple numerical',
    value: 'simple numerical',
    template: `DOCUMENT();
# Description
#   Problem_Set
#   Chapter
#   Question
# EndDescription

      loadMacros(
      "PGstandard.pl",
      "MathObjects.pl",
      "PGunion.pl",
      "PGcourse.pl",
      );

      @ans = ();       # add more answers to array if needed
      $ans[0] = Real("12345");

      BEGIN_TEXT
      text
      $BR \\{ans_rule(5)\\}
      END_TEXT

      $showPartialCorrectAnswers = 0;

      foreach my $i (0..0){     # change end condition based on @ans size
        ANS($ans[$i]->cmp() );
      }

ENDDOCUMENT();
`
  },
  {
    text: 'Pre-existing problem',
    value: 'pre-existing problem'
  }]
