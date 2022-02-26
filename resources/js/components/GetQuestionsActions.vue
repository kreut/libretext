<template>
  <div>
  <span v-if="!assignmentQuestion.in_current_assignment">
    <b-button
      variant="primary"
      class="p-1"
      @click.prevent="$emit('addQuestions',[assignmentQuestion])"
    ><span :aria-label="`Add ${assignmentQuestion.title} to the assignment`">+</span>
    </b-button>
    <b-tooltip
      :target="getTooltipTarget('add-question-to-assignment',assignmentQuestion.question_id)"
      delay="1000"
      triggers="hover focus"
      :title="`Add ${assignmentQuestion.my_favorites_folder_name} to the assignment`"
    >
      Add {{ assignmentQuestion.title }} to the assignment
    </b-tooltip>
  </span>
    <span v-if="assignmentQuestion.in_current_assignment">
    <b-button
      :id="getTooltipTarget('remove-question-from-assignment',assignmentQuestion.question_id)"
      variant="danger"
      class="p-1"
      @click.prevent="$emit('initRemoveAssignmentQuestion',assignmentQuestion)"
    ><span :aria-label="`Remove ${assignmentQuestion.title} from the assignment`">-</span>
    </b-button>
    <b-tooltip
      :target="getTooltipTarget('remove-question-from-assignment',assignmentQuestion.question_id)"
      delay="1000"
      triggers="hover focus"
      :title="`Remove ${assignmentQuestion.my_favorites_folder_name} from the assignment`"
    >
      Remove {{ assignmentQuestion.title }} from the assignment
    </b-tooltip>
  </span>
    <span v-if="questionSource !== 'my_favorites'">

    <span v-show="!assignmentQuestion.my_favorites_folder_id">
      <a
        href=""
        @click.prevent="$emit('initSaveToMyFavorites',[assignmentQuestion.question_id])"
      >
        <font-awesome-icon
          class="text-muted"
          :icon="heartIcon"
          :aria-label="`Add ${assignmentQuestion.title} to My Favorites`"
        />
      </a>
    </span>
    <span v-if="assignmentQuestion.my_favorites_folder_id">
      <a :id="getTooltipTarget('remove-from-my-favorites',assignmentQuestion.question_id)"
         href=""
         @click.prevent="$emit('removeMyFavoritesQuestion',assignmentQuestion.my_favorites_folder_id,assignmentQuestion.question_id)"
      >
        <font-awesome-icon
          class="text-danger"
          :icon="heartIcon"
          :aria-label="`Remove from ${assignmentQuestion.my_favorites_folder_name}`"
        />
      </a>
      <b-tooltip
        :target="getTooltipTarget('remove-from-my-favorites',assignmentQuestion.question_id)"
        delay="1000"
        triggers="hover focus"
        :title="`Move from ${assignmentQuestion.my_favorites_folder_name} or remove`"
      >
        Remove from the My Favorites folder {{
          assignmentQuestion.my_favorites_folder_name
        }}
      </b-tooltip>
    </span>
  </span>
    <span v-if="questionSource === 'my_favorites'">
    <a
      href=""
      @click.prevent="$emit('removeMyFavoritesQuestion',assignmentQuestion.my_favorites_folder_id,assignmentQuestion.question_id)"
    >
      <b-icon icon="trash"
              class="text-muted"
              :aria-label="`Remove from ${assignmentQuestion.my_favorites_folder_name}`"
      />
    </a>
    <b-tooltip
      :target="getTooltipTarget('remove-from-my-favorites-within-my-favorites',assignmentQuestion.question_id)"
      delay="1000"
      triggers="hover focus"
      :title="`Remove from ${assignmentQuestion.my_favorites_folder_name}`"
    >
      Remove from the My Favorites folder {{
        assignmentQuestion.my_favorites_folder_name
      }}
    </b-tooltip>
  </span>
  </div>
</template>

<script>
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

export default {
  name: 'GetQuestionsActions',
  components: { FontAwesomeIcon },
  props: {
    assignmentQuestion: {
      type: Object,
      default: () => {
      }
    },
    heartIcon: {
      type: Object,
      default: () => {
      }
    },
    questionSource: {
      type: String,
      default: ''
    },
    removeMyFavoritesQuestion: {
      type: Function,
      default: () => {
      }
    }
  },
  created () {
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
  }
}
</script>

<style scoped>

</style>
