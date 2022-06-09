<?php

namespace App\Http\Controllers;

use App\QtiImport;
use Illuminate\Http\Request;

class QtiTestingController extends Controller
{
    /**
     * @throws \Exception
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
}
