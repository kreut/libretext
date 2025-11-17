<template>
  <div>
    <table id="matching-table" class="table table-striped">
      <thead>
      <tr>
        <th scope="col">
          Term to match
        </th>
        <th scope="col">
          Chosen match
        </th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="(item,index) in termsToMatch" :key="`matching-answer-${item.identifier}`">
        <th scope="row">
          <div v-html="formatQuestionMediaPlayer(item.termToMatch)"/>
        </th>
        <td :class="qtiJson.studentResponse ? 'd-flex' : ''">
          <b-dropdown :id="`matching-answer-${item.identifier}`"
                      :html="getChosenMatch(item)"
                      class="matching-dropdown m-md-2"
                      no-flip
                      :variant="item.chosenMatchIdentifier === null ? 'secondary' : 'info'"
          >
            <b-dropdown-item v-for="possibleMatch in nonNullPossibleMatches"
                             :id="`dropdown-${possibleMatch.identifier}`"
                             :key="`possible-match-${possibleMatch.identifier}`"
                             style="overflow-x:auto;overflow-y:auto"
                             @click="updateChosenMatch(item, possibleMatch)"
            >
              <span v-html="possibleMatch.matchingTerm"/>
            </b-dropdown-item>
          </b-dropdown>
          <div v-if="qtiJson.studentResponse
              && qtiJson.studentResponse[index].chosenMatchIdentifier === item.chosenMatchIdentifier
              && qtiJson.studentResponse[index].hasOwnProperty('answeredCorrectly')"
               class="mt-3 ml-1"
          >
            <b-icon-check-circle-fill v-if="qtiJson.studentResponse[index].answeredCorrectly"
                                      class="text-success mr-2"
                                      scale="1.5"
            />
            <b-icon-x-circle-fill v-if="!qtiJson.studentResponse[index].answeredCorrectly"
                                  class="text-danger mr-2"
                                  scale="1.5"
            />
          </div>
          <input type="hidden" class="form-control is-invalid">
          <div class="help-block invalid-feedback">
            {{ item.errorMessage }}
          </div>
        </td>
      </tr>
      </tbody>
    </table>
    <b-card v-if="termsToMatchWithFeedback.length"
            border-variant="info"
            header="Feedback"
            header-bg-variant="info"
            header-text-variant="white"
            header-class="pt-2 pb-2 pl-3"
    >
      <b-table
        :items="termsToMatchWithFeedback"
        :fields="feedbackFields"
        aria-label="Feedback"
        striped
        hover
        responsive
        head-variant="info"
        :no-border-collapse="true"
      >
        <template v-slot:cell(termToMatch)="data">
          <div v-html="data.item.termToMatch"/>
        </template>
        <template v-slot:cell(feedback)="data">
          <div v-html="data.item.feedback"/>
        </template>
      </b-table>
    </b-card>
  </div>
</template>

<script>
import $ from 'jquery'
import { formatQuestionMediaPlayer } from '../../helpers/Questions'

export default {
  name: 'MatchingViewer',
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    },
    showResponseFeedback: {
      type: Boolean,
      default: true
    }
  },
  data: () => ({
    feedbackFields: ['termToMatch', 'feedback'],
    showQtiAnswer: false,
    termsToMatch: [],
    doNotRepeatErrorMessage: '',
    matchingFeedbacks: ''
  }),
  computed: {
    termsToMatchWithFeedback () {
      return this.qtiJson.termsToMatch.filter(item => item.feedback && item.feedback !== '')
    },
    nonNullPossibleMatches () {
      return this.possibleMatches.filter(possibleMatch => possibleMatch.identifier !== null)
    }
  },
  mounted () {
    this.termsToMatch = this.qtiJson.termsToMatch
    this.possibleMatches = this.qtiJson.possibleMatches
    this.getMatchingFeedbacks()
    let html
    let chooseMatchMessage = 'Choose a match'
    for (let i = 0; i < this.possibleMatches.length; i++) {
      let possibleMatch = this.possibleMatches[i]
      html = html = $.parseHTML(possibleMatch.matchingTerm)
      if ($(html).find('img').length) {
        chooseMatchMessage = 'Choose a match from the images below'
        $(html).find('img').each(function () {
          $(this).attr('width', '')
          $(this).attr('height', '')
          possibleMatch.matchingTerm = $(this).prop('outerHTML')
        })
      }
    }
    this.possibleMatches.push({ identifier: null, matchingTerm: chooseMatchMessage })
    for (let i = 0; i < this.termsToMatch.length; i++) {
      if (this.qtiJson.studentResponse) {
        this.termsToMatch[i].chosenMatchIdentifier = this.qtiJson.studentResponse.find(response => response.identifier === this.termsToMatch[i].identifier).chosenMatchIdentifier
      } else {
        this.termsToMatch[i].chosenMatchIdentifier = null
        this.termsToMatch[i].errorMessage = ''
      }
    }
    if (this.qtiJson.studentResponse) {
      console.log(this.qtiJson.studentResponse)
    }
  },
  methods: {
    formatQuestionMediaPlayer,
    getMatchingFeedbacks () {
      for (let i = 0; i < this.qtiJson.termsToMatch.length; i++) {
        let termToMatch = this.qtiJson.termsToMatch[i]
        console.log(termToMatch)
      }
    },
    getChosenMatch (item) {
      return this.possibleMatches.find(possibleMatch => possibleMatch.identifier === item.chosenMatchIdentifier).matchingTerm.replace('<p>', '').replace('</p>', '')
    },
    updateChosenMatch (item, possibleMatch) {
      item.errorMessage = ''
      item.chosenMatchIdentifier = possibleMatch.identifier
      this.$forceUpdate()
      this.$nextTick(() => {
        $(`#matching-answer-${item.identifier}`).find('.dropdown-toggle')
          .removeClass('dropdown-toggle')
          .css('border-radius', '4px')
      })
    }
  }
}
</script>
