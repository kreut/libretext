<?php

namespace App\Http\Controllers;

use App\QtiImport;
use Exception;
use Illuminate\Http\Request;

class QtiTestingController extends Controller
{
    /**
     * @throws Exception
     */
    function matching(QtiImport $qtiImport)
    {
        $xml = <<<EOD
<item ident="g786bc4067af8cb42adc432381846c77e" title="Question">
        <itemmetadata>
          <qtimetadata>
            <qtimetadatafield>
              <fieldlabel>question_type</fieldlabel>
              <fieldentry>matching_question</fieldentry>
            </qtimetadatafield>
            <qtimetadatafield>
              <fieldlabel>points_possible</fieldlabel>
              <fieldentry>1.0</fieldentry>
            </qtimetadatafield>
            <qtimetadatafield>
              <fieldlabel>original_answer_ids</fieldlabel>
              <fieldentry>936,2610,7307</fieldentry>
            </qtimetadatafield>
            <qtimetadatafield>
              <fieldlabel>assessment_question_identifierref</fieldlabel>
              <fieldentry>gf47b767d37e06559ff801f2d253307ba</fieldentry>
            </qtimetadatafield>
          </qtimetadata>
        </itemmetadata>
        <presentation>
          <material>
            <mattext texttype="text/html">&lt;div&gt;&lt;p&gt;&lt;span style="font-size: 12pt;"&gt;As we have seen, writers can have neutral, negative, or positive attitudes about counterarguments. Particular phrases can be used to signal that the writer is introducing the counterargument and at the same time indicate their attitude towards it.&amp;nbsp;&lt;/span&gt;&lt;/p&gt;
&lt;p&gt;&lt;span style="font-size: 12pt;"&gt;Match each sample phrase listed below with the attitude toward the counterargument it signals.&lt;/span&gt;&lt;/p&gt;&lt;/div&gt;</mattext>
          </material>
          <response_lid ident="response_936">
            <material>
              <mattext texttype="text/plain">It is a popular misconception that_____________.</mattext>
            </material>
            <render_choice>
              <response_label ident="1156">
                <material>
                  <mattext>The writer thinks the counterargument is completely wrong.</mattext>
                </material>
              </response_label>
              <response_label ident="7940">
                <material>
                  <mattext>The writer is about to describe a counterargument without giving their opinion yet.</mattext>
                </material>
              </response_label>
              <response_label ident="3089">
                <material>
                  <mattext>The writer sees some merit in the counterargument. They agree with it even though it hurts their argument.</mattext>
                </material>
              </response_label>
              <response_label ident="8352">
                <material>
                  <mattext>The writer is explaining an idea they want to promote.</mattext>
                </material>
              </response_label>
            </render_choice>
          </response_lid>
          <response_lid ident="response_2610">
            <material>
              <mattext texttype="text/plain">Some will take issue with _____________, arguing that _____________.</mattext>
            </material>
            <render_choice>
              <response_label ident="1156">
                <material>
                  <mattext>The writer thinks the counterargument is completely wrong.</mattext>
                </material>
              </response_label>
              <response_label ident="7940">
                <material>
                  <mattext>The writer is about to describe a counterargument without giving their opinion yet.</mattext>
                </material>
              </response_label>
              <response_label ident="3089">
                <material>
                  <mattext>The writer sees some merit in the counterargument. They agree with it even though it hurts their argument.</mattext>
                </material>
              </response_label>
              <response_label ident="8352">
                <material>
                  <mattext>The writer is explaining an idea they want to promote.</mattext>
                </material>
              </response_label>
            </render_choice>
          </response_lid>
          <response_lid ident="response_7307">
            <material>
              <mattext texttype="text/plain">We must admit that_____________.</mattext>
            </material>
            <render_choice>
              <response_label ident="1156">
                <material>
                  <mattext>The writer thinks the counterargument is completely wrong.</mattext>
                </material>
              </response_label>
              <response_label ident="7940">
                <material>
                  <mattext>The writer is about to describe a counterargument without giving their opinion yet.</mattext>
                </material>
              </response_label>
              <response_label ident="3089">
                <material>
                  <mattext>The writer sees some merit in the counterargument. They agree with it even though it hurts their argument.</mattext>
                </material>
              </response_label>
              <response_label ident="8352">
                <material>
                  <mattext>The writer is explaining an idea they want to promote.</mattext>
                </material>
              </response_label>
            </render_choice>
          </response_lid>
        </presentation>
        <resprocessing>
          <outcomes>
            <decvar maxvalue="100" minvalue="0" varname="SCORE" vartype="Decimal"/>
          </outcomes>
          <respcondition>
            <conditionvar>
              <varequal respident="response_936">1156</varequal>
            </conditionvar>
            <setvar varname="SCORE" action="Add">33.33</setvar>
          </respcondition>
          <respcondition>
            <conditionvar>
              <not>
                <varequal respident="response_936">1156</varequal>
              </not>
            </conditionvar>
            <displayfeedback feedbacktype="Response" linkrefid="936_fb"/>
          </respcondition>
          <respcondition>
            <conditionvar>
              <varequal respident="response_2610">7940</varequal>
            </conditionvar>
            <setvar varname="SCORE" action="Add">33.33</setvar>
          </respcondition>
          <respcondition>
            <conditionvar>
              <not>
                <varequal respident="response_2610">7940</varequal>
              </not>
            </conditionvar>
            <displayfeedback feedbacktype="Response" linkrefid="2610_fb"/>
          </respcondition>
          <respcondition>
            <conditionvar>
              <varequal respident="response_7307">3089</varequal>
            </conditionvar>
            <setvar varname="SCORE" action="Add">33.33</setvar>
          </respcondition>
          <respcondition>
            <conditionvar>
              <not>
                <varequal respident="response_7307">3089</varequal>
              </not>
            </conditionvar>
            <displayfeedback feedbacktype="Response" linkrefid="7307_fb"/>
          </respcondition>
        </resprocessing>
        <itemfeedback ident="936_fb">
          <flow_mat>
            <material>
              <mattext texttype="text/html">&lt;p&gt;&lt;span style="font-size: 12pt;"&gt;“Misconception” is a negative word applied to the counterargument. Such a phrase can be key to determining the writer’s attitude.&amp;nbsp; See &lt;a class="external" href="https://human.libretexts.org/Bookshelves/Composition/Advanced_Composition/Book%3A_How_Arguments_Work_-_A_Guide_to_Writing_and_Analyzing_Texts_in_College_(Mills)/02%3A_Reading_to_Figure_out_the_Argument/2.06%3A_Finding_the_Counterarguments" target="_blank"&gt;&lt;span&gt;Section 2.6: Finding the Counterarguments&lt;/span&gt;&lt;/a&gt; for more on identifying the attitude to a counterargument.&lt;/span&gt;&lt;/p&gt;
&lt;p&gt;&amp;nbsp;&lt;/p&gt;</mattext>
            </material>
          </flow_mat>
        </itemfeedback>
        <itemfeedback ident="2610_fb">
          <flow_mat>
            <material>
              <mattext texttype="text/html">&lt;p&gt;&lt;span style="font-size: 12pt;"&gt;This phrasing announces that there is a counterargument but doesn't present it in a positive or negative way. See &lt;a class="external" href="https://human.libretexts.org/Bookshelves/Composition/Advanced_Composition/Book%3A_How_Arguments_Work_-_A_Guide_to_Writing_and_Analyzing_Texts_in_College_(Mills)/02%3A_Reading_to_Figure_out_the_Argument/2.06%3A_Finding_the_Counterarguments" target="_blank"&gt;&lt;span&gt;Section 2.6: Finding the Counterarguments&lt;/span&gt;&lt;/a&gt; for more on identifying the attitude to a counterargument.&lt;/span&gt;&lt;/p&gt;
&lt;p&gt;&amp;nbsp;&lt;/p&gt;
&lt;p&gt;&amp;nbsp;&lt;/p&gt;</mattext>
            </material>
          </flow_mat>
        </itemfeedback>
        <itemfeedback ident="7307_fb">
          <flow_mat>
            <material>
              <mattext texttype="text/html">&lt;span style="font-size: 12pt;"&gt;“Admit” implies that the writer considers the idea to be true even though it makes the writer or the writer’s argument look bad.&amp;nbsp; See&amp;nbsp;&lt;a class="external" href="https://human.libretexts.org/Bookshelves/Composition/Advanced_Composition/Book%3A_How_Arguments_Work_-_A_Guide_to_Writing_and_Analyzing_Texts_in_College_(Mills)/02%3A_Reading_to_Figure_out_the_Argument/2.06%3A_Finding_the_Counterarguments" target="_blank"&gt;&lt;span&gt;Section 2.6: Finding the Counterarguments&lt;/span&gt;&lt;/a&gt; for more on identifying the attitude to a counterargument.&lt;/span&gt;</mattext>
            </material>
          </flow_mat>
        </itemfeedback>
      </item>
EOD;
        $string_xml = $xml;
        $xml = $qtiImport->cleanUpXml($xml);
        $xml_array = json_decode(json_encode($xml), true);
        dd($qtiImport->processMatching($string_xml, $xml_array));


    }

    public function simpleChoice(QtiImport $qtiImport)
    {

        $xml = <<<EOD
<item ident="g208dc84b47068a687ee081fc58c26231" title="000110802200 version 01">
        <itemmetadata>
          <qtimetadata>
            <qtimetadatafield>
              <fieldlabel>question_type</fieldlabel>
              <fieldentry>multiple_choice_question</fieldentry>
            </qtimetadatafield>
            <qtimetadatafield>
              <fieldlabel>points_possible</fieldlabel>
              <fieldentry>1</fieldentry>
            </qtimetadatafield>
            <qtimetadatafield>
              <fieldlabel>original_answer_ids</fieldlabel>
              <fieldentry>56605,29630,22279,90788</fieldentry>
            </qtimetadatafield>
            <qtimetadatafield>
              <fieldlabel>assessment_question_identifierref</fieldlabel>
              <fieldentry>gdd50f41c97f7efc96ec885ea649a4ef9</fieldentry>
            </qtimetadatafield>
          </qtimetadata>
        </itemmetadata>
        <presentation>
          <material>
            <mattext texttype="text/html">&lt;div&gt;Consider the following data for an experiment involving the photoelectric effect using cathodes A and B. &lt;br&gt;&lt;img src="IMS-CC-FILEBASE$/Quiz%20Files/PhotelectricEffectEinsteinExplanation_4587717/0802200.gif" alt=""&gt;&lt;br&gt;&lt;br&gt;The work function, φ, for surface B is approximately&lt;/div&gt;</mattext>
          </material>
          <response_lid ident="response1" rcardinality="Single">
            <render_choice>
              <response_label ident="56605">
                <material>
                  <mattext texttype="text/html">4.96x10&lt;sup&gt;-19&lt;/sup&gt; J</mattext>
                </material>
              </response_label>
              <response_label ident="29630">
                <material>
                  <mattext texttype="text/html">6.62x10&lt;sup&gt;-19&lt;/sup&gt; J</mattext>
                </material>
              </response_label>
              <response_label ident="22279">
                <material>
                  <mattext texttype="text/html">2.65x10&lt;sup&gt;-31&lt;/sup&gt; J</mattext>
                </material>
              </response_label>
              <response_label ident="90788">
                <material>
                  <mattext texttype="text/html">1.99x10&lt;sup&gt;-31&lt;/sup&gt; J</mattext>
                </material>
              </response_label>
            </render_choice>
          </response_lid>
        </presentation>
        <resprocessing>
          <outcomes>
            <decvar maxvalue="100" minvalue="0" varname="SCORE" vartype="Decimal"/>
          </outcomes>
          <respcondition continue="No">
            <conditionvar>
              <varequal respident="response1">29630</varequal>
            </conditionvar>
            <setvar action="Set" varname="SCORE">100</setvar>
            <displayfeedback feedbacktype="Response" linkrefid="correct_fb"/>
          </respcondition>
          <respcondition continue="Yes">
            <conditionvar>
              <other/>
            </conditionvar>
            <displayfeedback feedbacktype="Response" linkrefid="general_incorrect_fb"/>
          </respcondition>
        </resprocessing>
        <itemfeedback ident="correct_fb">
          <flow_mat>
            <material>
              <mattext texttype="text/html">Correct. &lt;hr&gt;&lt;br&gt;The minimum energy needed to dislodge an electron from the surface (the "work function", φ) is hν&lt;sub&gt;min&lt;/sub&gt;, where h is Planck's constant (6.626x10&lt;sup&gt;-34&lt;/sup&gt; J s) and ν&lt;sub&gt;min&lt;/sub&gt; is the threshold frequency. From the plot, we can get the threshold wavelength (λ&lt;sub&gt;max&lt;/sub&gt;) to be 300 nm, which we first convert to meters:&lt;br&gt;&lt;br&gt;λ&lt;sub&gt;max&lt;/sub&gt; = 300 nm = 3.00x10&lt;sup&gt;-7&lt;/sup&gt; m&lt;br&gt;&lt;br&gt;Since ν = c/λ, where c=speed of light, we can calculate ν&lt;sub&gt;min&lt;/sub&gt;:&lt;br&gt;&lt;br&gt;ν&lt;sub&gt;min&lt;/sub&gt; = (2.998x10&lt;sup&gt;8&lt;/sup&gt; m/s) / 3.00x10&lt;sup&gt;-7&lt;/sup&gt; m = 9.993x10&lt;sup&gt;14&lt;/sup&gt; s&lt;sup&gt;-1&lt;/sup&gt;&lt;br&gt;&lt;br&gt;Therefore: φ = (6.626x10&lt;sup&gt;-34&lt;/sup&gt; J s) (9.993x10&lt;sup&gt;14&lt;/sup&gt; s&lt;sup&gt;-1&lt;/sup&gt;) = 6.62x10&lt;sup&gt;-19&lt;/sup&gt; J</mattext>
            </material>
          </flow_mat>
        </itemfeedback>
        <itemfeedback ident="general_incorrect_fb">
          <flow_mat>
            <material>
              <mattext texttype="text/html">Incorrect. &lt;hr&gt;&lt;br&gt;The minimum energy needed to dislodge an electron from the surface (the "work function", φ) is hν&lt;sub&gt;min&lt;/sub&gt;, where h is Planck's constant (6.626x10&lt;sup&gt;-34&lt;/sup&gt; J s) and ν&lt;sub&gt;min&lt;/sub&gt; is the threshold frequency. From the plot, we can get the threshold wavelength (λ&lt;sub&gt;max&lt;/sub&gt;) to be 300 nm, which we first convert to meters:&lt;br&gt;&lt;br&gt;λ&lt;sub&gt;max&lt;/sub&gt; = 300 nm = 3.00x10&lt;sup&gt;-7&lt;/sup&gt; m&lt;br&gt;&lt;br&gt;Since ν = c/λ, where c=speed of light, we can calculate ν&lt;sub&gt;min&lt;/sub&gt;:&lt;br&gt;&lt;br&gt;ν&lt;sub&gt;min&lt;/sub&gt; = (2.998x10&lt;sup&gt;8&lt;/sup&gt; m/s) / 3.00x10&lt;sup&gt;-7&lt;/sup&gt; m = 9.993x10&lt;sup&gt;14&lt;/sup&gt; s&lt;sup&gt;-1&lt;/sup&gt;&lt;br&gt;&lt;br&gt;Therefore: φ = (6.626x10&lt;sup&gt;-34&lt;/sup&gt; J s) (9.993x10&lt;sup&gt;14&lt;/sup&gt; s&lt;sup&gt;-1&lt;/sup&gt;) = 6.62x10&lt;sup&gt;-19&lt;/sup&gt; J</mattext>
            </material>
          </flow_mat>
        </itemfeedback>
      </item>
EOD;

        $xml = $qtiImport->cleanUpXml($xml);
        $xml_array = json_decode(json_encode($xml), true);
        dd($qtiImport->processSimpleChoice($xml_array, 'multiple_choice'));

    }

    public function simpleChoiceWithoutVarEqual(QtiImport $qtiImport)
    {
        $xml = <<<EOD
<item ident="g0fae7ae2a63aa7adcca9ca89491f0423" title="Question">
        <itemmetadata>
          <qtimetadata>
            <qtimetadatafield>
              <fieldlabel>question_type</fieldlabel>
              <fieldentry>multiple_choice_question</fieldentry>
            </qtimetadatafield>
            <qtimetadatafield>
              <fieldlabel>points_possible</fieldlabel>
              <fieldentry>1.0</fieldentry>
            </qtimetadatafield>
            <qtimetadatafield>
              <fieldlabel>original_answer_ids</fieldlabel>
              <fieldentry>5678,4300,2937,2405</fieldentry>
            </qtimetadatafield>
            <qtimetadatafield>
              <fieldlabel>assessment_question_identifierref</fieldlabel>
              <fieldentry>gdcdd97c05b8620b021ee74ae7c44b6e0</fieldentry>
            </qtimetadatafield>
          </qtimetadata>
        </itemmetadata>
        <presentation>
          <material>
            <mattext texttype="text/html">&lt;div&gt;&lt;p&gt;What is the most credible type of source to use in an academic essay?&lt;/p&gt;&lt;/div&gt;</mattext>
          </material>
          <response_lid ident="response1" rcardinality="Single">
            <render_choice>
              <response_label ident="5678">
                <material>
                  <mattext texttype="text/plain">Reports, articles, and books from credible non-academic sources</mattext>
                </material>
              </response_label>
              <response_label ident="4300">
                <material>
                  <mattext texttype="text/plain">Agenda-driven pieces or pieces where the website creator or the author's qualifications are unknown</mattext>
                </material>
              </response_label>
              <response_label ident="2937">
                <material>
                  <mattext texttype="text/plain">Peer-reviewed academic publications</mattext>
                </material>
              </response_label>
              <response_label ident="2405">
                <material>
                  <mattext texttype="text/plain">Short pieces from newspapers or credible websites</mattext>
                </material>
              </response_label>
            </render_choice>
          </response_lid>
        </presentation>
        <resprocessing>
          <outcomes>
            <decvar maxvalue="100" minvalue="0" varname="SCORE" vartype="Decimal"/>
          </outcomes>
          <respcondition continue="Yes">
            <conditionvar>
              <other/>
            </conditionvar>
            <displayfeedback feedbacktype="Response" linkrefid="general_fb"/>
          </respcondition>
          <respcondition continue="Yes">
            <conditionvar>
              <varequal respident="response1">5678</varequal>
            </conditionvar>
            <displayfeedback feedbacktype="Response" linkrefid="5678_fb"/>
          </respcondition>
          <respcondition continue="Yes">
            <conditionvar>
              <varequal respident="response1">4300</varequal>
            </conditionvar>
            <displayfeedback feedbacktype="Response" linkrefid="4300_fb"/>
          </respcondition>
          <respcondition continue="Yes">
            <conditionvar>
              <varequal respident="response1">2937</varequal>
            </conditionvar>
            <displayfeedback feedbacktype="Response" linkrefid="2937_fb"/>
          </respcondition>
          <respcondition continue="Yes">
            <conditionvar>
              <varequal respident="response1">2405</varequal>
            </conditionvar>
            <displayfeedback feedbacktype="Response" linkrefid="2405_fb"/>
          </respcondition>
          <respcondition continue="No">
            <conditionvar>
              <varequal respident="response1">2937</varequal>
            </conditionvar>
            <setvar action="Set" varname="SCORE">100</setvar>
          </respcondition>
        </resprocessing>
        <itemfeedback ident="general_fb">
          <flow_mat>
            <material>
              <mattext texttype="text/html">&lt;p&gt;See &lt;a class="internal" title="6.5: Types of Sources" href="https://human.libretexts.org/Bookshelves/Composition/Advanced_Composition/Book%3A_How_Arguments_Work_-_A_Guide_to_Writing_and_Analyzing_Texts_in_College_(Mills)/06%3A_The_Research_Process/05%3A_Types_of_Sources"&gt;6.5: Types of Sources&lt;/a&gt;.&lt;/p&gt;</mattext>
            </material>
          </flow_mat>
        </itemfeedback>
        <itemfeedback ident="5678_fb">
          <flow_mat>
            <material>
              <mattext texttype="text/html">&lt;p&gt;While reports, articles, and books from credible non-academic sources are well researched and they sometimes include even-handed descriptions of an event or state of the world, they are not as strong as peer-reviewed sources because their initial research on events or trends has not yet been analyzed in&amp;nbsp; academic literature. These kinds of sources are found on various websites of relevant agencies, Google searches using (site: *.gov or site: *.org), academic article databases, and more.&lt;/p&gt;
&lt;p&gt;Peer-reviewed academic publications are the most credible sources because they go through rigorous research and analysis and they provide strong evidence for claims and references to other high-quality sources. These are the most credible sources to use in your essays. You can find peer-reviewed essays on Google Scholar, library catalogs, and through the academic article databases.&lt;/p&gt;</mattext>
            </material>
          </flow_mat>
        </itemfeedback>
        <itemfeedback ident="4300_fb">
          <flow_mat>
            <material>
              <mattext texttype="text/html">Agenda-driven pieces or pieces where the website creator, author, or the author's qualifications are unknown, vary in thoughtfulness and credibility. This kind of source may represent a particular position within a debate; however, a source like this will more often provide keywords, background knowledge on the topic, and clues about higher quality sources.Peer-reviewed academic publications are the most credible sources because they go through rigorous research and analysis and they provide strong evidence for claims and references to other high-quality sources. These are the most credible sources to use in your essays. You can find peer-reviewed essays on Google Scholar, library catalogs, and through the academic article databases.</mattext>
            </material>
          </flow_mat>
        </itemfeedback>
        <itemfeedback ident="2937_fb">
          <flow_mat>
            <material>
              <mattext texttype="text/html">&lt;p&gt;That is correct!&lt;/p&gt;
&lt;p&gt;Peer-reviewed academic publications are the most credible sources because they go through rigorous research and analysis and they provide strong evidence for claims and references to other high-quality sources. These are the most credible sources to use in your essays. You can find peer-reviewed essays on Google Scholar, library catalogs, and through the academic article databases.&lt;/p&gt;</mattext>
            </material>
          </flow_mat>
        </itemfeedback>
        <itemfeedback ident="2405_fb">
          <flow_mat>
            <material>
              <mattext texttype="text/html">&lt;p&gt;While these can be credible when it comes to factual information, they do not represent rigorous research or analysis by an expert in the field. This kind of source is usually just a simple reporting of events, research findings, or policy changes. It is not enough to base a scholarly argument on; however, it may contain references to other sources you can research.&lt;/p&gt;
&lt;p&gt;Peer-reviewed academic publications are the most credible sources because they go through rigorous research and analysis and they provide strong evidence for claims and references to other high-quality sources. These are the most credible sources to use in your essays. You can find peer-reviewed essays on Google Scholar, library catalogs, and through the academic article databases.&lt;/p&gt;</mattext>
            </material>
          </flow_mat>
        </itemfeedback>
      </item>
EOD;
        $xml = $qtiImport->cleanUpXml($xml);
        $xml_array = json_decode(json_encode($xml), true);
        dd($qtiImport->processSimpleChoice($xml_array, 'multiple_choice'));
    }
}
