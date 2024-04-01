<template>
  <div>
    <b-alert show variant="info">
      Create a question with fill in the blanks by underlining
      the correct responses. Example. A <u>stitch</u> in time saves <u>nine</u>. If you would like to
      accept multiple answers, separate them with a vertical bar: "|". Example.
      <u>January|February</u> is a month that comes before March.
    </b-alert>
    <QuestionMediaUpload />
    <ckeditor
      id="qtiItemBodyTextEntryInteraction"
      v-model="qtiJson.itemBody.textEntryInteraction"
      tabindex="0"
      required
      :config="richEditorConfig"
      :class="{ 'is-invalid': questionForm.errors.has('qti_item_body')}"
      class="pb-3"
      @namespaceloaded="onCKEditorNamespaceLoaded"
      @ready="handleFixCKEditor()"
      @keydown="questionForm.errors.clear('qti_item_body')"
    />
    <has-error :form="questionForm" field="qti_item_body" />
    <table v-if="textEntryInteractions.length" class="table table-striped">
      <thead>
        <tr>
          <th scope="col">
            Correct Response
          </th>
          <th scope="col">
            Matching Type
            <QuestionCircleTooltip :id="'matching-type-tooltip'" />
            <b-tooltip target="matching-type-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              Example. 'the city' would be considered correct if the answer really is 'the city' if you choose
              Exact. If you choose Substring and student
              submits 'city'.
            </b-tooltip>
          </th>
          <th scope="col">
            Case Sensitive
            <QuestionCircleTooltip :id="'case-sensitive-tooltip'" />
            <b-tooltip target="case-sensitive-tooltip-tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              Example. 'new york' would be correct if the correct answer is 'New York' and you choose 'no' for
              Case Sensitive. Otherwise, it would be
              considered incorrect.
            </b-tooltip>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(uTag,index) in uTags" :key="`uTag-${index}`">
          <td>
            <div v-if="!uTag.includes('|')">
              {{ uTag }}
            </div>
            <div v-if="uTag.includes('|')">
              <ol class="pl-3">
                <li v-for="(uTagOption, uTagOptionIndex) in uTag.split('|')"
                    :key="`uTag-Option-Index-${index}-${uTagOptionIndex}`"
                >
                  {{ uTagOption }}
                </li>
              </ol>
            </div>
          </td>
          <td>
            <b-form-radio v-model="textEntryInteractions[index].matchingType" :name="`matching_type-${index}`"
                          value="exact"
            >
              Exact
            </b-form-radio>
            <b-form-radio v-model="textEntryInteractions[index].matchingType" :name="`matching_type-${index}`"
                          value="substring"
            >
              Substring
            </b-form-radio>
          </td>
          <td>
            <b-form-radio v-model="textEntryInteractions[index].caseSensitive" :name="`case_sensitive-${index}`"
                          value="no"
            >
              No
            </b-form-radio>
            <b-form-radio v-model="textEntryInteractions[index].caseSensitive" :name="`case_sensitive-${index}`"
                          value="yes"
            >
              Yes
            </b-form-radio>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import { fixCKEditor } from '~/helpers/accessibility/fixCKEditor'
import CKEditor from 'ckeditor4-vue'
import QuestionMediaUpload from '../QuestionMediaUpload.vue'

export default {
  name: 'FillInTheBlank',
  components: {
    QuestionMediaUpload,
    ckeditor: CKEditor.component
  },
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    },
    richEditorConfig: {
      type: Object,
      default: () => {
      }
    },
    questionForm: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    textEntryInteractions: []
  }),
  computed: {
    uTags () {
      if (this.qtiJson.itemBody.textEntryInteraction) {
        const regex = /(<u>.*?<\/u>)/
        let matches = String(this.qtiJson.itemBody.textEntryInteraction).split(regex).filter(Boolean)
        let uTags = []
        if (matches && matches.length) {
          for (let i = 0; i < matches.length; i++) {
            let match = matches[i]
            if (match.includes('<u>') && match.includes('</u>')) {
              match.replace('<u>', '').replace('</u>', '')
              uTags.push(this.htmlDecode(match)) // foreign language issues
              this.questionForm.errors.clear('qti_item_body')
            }
          }
        }
        if (!uTags.length) {
          uTags = null
        }
        console.log(uTags)
        return uTags
      } else {
        return []
      }
    }
  },
  mounted () {
    console.log(this.questionForm)
    for (let i = 0; i < 100; i++) {
      this.textEntryInteractions[i] = { matchingType: 'exact', caseSensitive: 'no' }
    }

    let correctResponse = this.qtiJson.responseDeclaration
      ? this.qtiJson.responseDeclaration.correctResponse
      : []
    for (let i = 0; i < correctResponse.length; i++) {
      this.qtiJson.itemBody.textEntryInteraction = this.qtiJson.itemBody.textEntryInteraction.replace('<u></u>', `<u>${correctResponse[i].value}</u>`)
    }
    for (let i = 0; i < correctResponse.length; i++) {
      this.textEntryInteractions[i] = {
        matchingType: correctResponse[i].matchingType,
        caseSensitive: correctResponse[i].caseSensitive
      }
    }
    for (let i = correctResponse.length; i < 100; i++) {
      this.textEntryInteractions[i] = { matchingType: 'exact', caseSensitive: 'no' }
    }
    console.log(this.textEntryInteractions)
  },
  methods: {
    htmlDecode (input) {
      let doc = new DOMParser().parseFromString(input, 'text/html')
      return doc.documentElement.textContent
    },
    handleFixCKEditor () {
      fixCKEditor(this)
    },
    onCKEditorNamespaceLoaded (CKEDITOR) {
      CKEDITOR.addCss('.cke_editable { font-size: 15px; }')
    },
    getFillInTheBlankResponseDeclarations () {
      let responseDeclarations = []
      console.log(this.uTags)
      if (this.uTags) {
        for (let i = 0; i < this.uTags.length; i++) {
          let uTag = this.uTags[i]
          console.log(uTag)
          let responseDeclaration = {
            'value': uTag,
            'matchingType': this.textEntryInteractions[i].matchingType,
            'caseSensitive': this.textEntryInteractions[i].caseSensitive
          }
          responseDeclarations.push(responseDeclaration)
        }
      }
      return responseDeclarations
    }
  }
}
</script>
