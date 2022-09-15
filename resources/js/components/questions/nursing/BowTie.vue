<template>
  <div>
    {{ qtiJson }}
    <b-container>
      <div class="pb-3">
        <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Actions To Take</h2>">
          <b-card-text>
            <p>Please create 2 correct actions to take and at least 1 distractor.</p>
            <div v-for="(actionToTake, actionToTakeIndex) in qtiJson.actionsToTake" :key="actionToTake.identifier"
                 class="pb-3"
            >
              <b-input-group>
                <b-form-input v-model="actionToTake.value"
                              :placeholder="actionToTake.correctResponse ? `Correct Action To Take ${actionToTakeIndex + 1}`
                            : `Distractor ${actionToTakeIndex -1}`"
                              :class="actionToTake.correctResponse ? 'form-control success-border' : ''"
                />
                <b-input-group-append v-if="!actionToTake.correctResponse">
                  <b-input-group-text>
                    <b-icon-trash
                      @click="removeBowTieItem('action to take','actionsToTake',actionToTake.identifier)"
                    />
                  </b-input-group-text>
                </b-input-group-append>
              </b-input-group>
              <ErrorMessage v-if="questionForm.errors.get('actions_to_take')
                              && JSON.parse(questionForm.errors.get('actions_to_take'))['specific']"
                            :message="JSON.parse(questionForm.errors.get('actions_to_take'))['specific'][actionToTake.identifier]"
              />
            </div>
            <ErrorMessage v-if="questionForm.errors.get('actions_to_take')
                            && JSON.parse(questionForm.errors.get('actions_to_take'))['general']"
                          class="pb-2"
                          :message="JSON.parse(questionForm.errors.get('actions_to_take'))['general']"
            />
            <b-button class="primary" size="sm" @click="addBowTieItem('actionsToTake')">
              Add Distractor
            </b-button>
          </b-card-text>
        </b-card>
      </div>
      <div class="pb-3">
        <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Potential Conditions</h2>">
          <p>Please create 1 correct potential condition and at least 1 distractor.</p>
          <b-card-text>
            <div v-for="(potentialCondition, potentialConditionIndex) in qtiJson.potentialConditions"
                 :key="potentialCondition.identifier"
                 class="pb-3"
            >
              <b-input-group>
                <b-form-input v-model="potentialCondition.value"
                              :placeholder="potentialCondition.correctResponse ? `Correct Potential Condition ${ potentialConditionIndex + 1}`
                            : `Distractor ${potentialConditionIndex}`"
                              :class="potentialCondition.correctResponse ? 'form-control success-border' : ''"
                />
                <b-input-group-append v-if="!potentialCondition.correctResponse">
                  <b-input-group-text>
                    <b-icon-trash
                      @click="removeBowTieItem('potential condition','potentialConditions',potentialCondition.identifier)"
                    />
                  </b-input-group-text>
                </b-input-group-append>
              </b-input-group>
              <ErrorMessage v-if="questionForm.errors.get('potential_conditions')
                              && JSON.parse(questionForm.errors.get('potential_conditions'))['specific']"
                            :message="JSON.parse(questionForm.errors.get('potential_conditions'))['specific'][potentialCondition.identifier]"
              />
            </div>
            <ErrorMessage v-if="questionForm.errors.get('potential_conditions')
                            && JSON.parse(questionForm.errors.get('potential_conditions'))['general']"
                          class="pb-2"
                          :message="JSON.parse(questionForm.errors.get('potential_conditions'))['general']"
            />

            <b-button class="primary" size="sm" @click="addBowTieItem('potentialConditions')">
              Add Distractor
            </b-button>
          </b-card-text>
        </b-card>
      </div>
      <div class="pb-3">
        <b-card header="default" header-html="<h2 class=&quot;h7&quot;>Parameters To Monitor</h2>">
          <b-card-text>
            <p>Please create 2 correct parameters to monitor and at least 1 distractor.</p>
            <div v-for="(parameterToMonitor, parameterToMonitorIndex) in qtiJson.parametersToMonitor"
                 :key="parameterToMonitor.identifier"
                 class="pb-3"
            >
              <b-input-group>
                <b-form-input v-model="parameterToMonitor.value"
                              :placeholder="parameterToMonitor.correctResponse ? `Correct Parameter to Monitor ${  parameterToMonitorIndex + 1}`
                            : `Distractor ${ parameterToMonitorIndex - 1}`"
                              :class="parameterToMonitor.correctResponse ? 'form-control success-border' : ''"
                />
                <b-input-group-append v-if="!parameterToMonitor.correctResponse">
                  <b-input-group-text>
                    <b-icon-trash
                      @click="removeBowTieItem('parameter to monitor','parametersToMonitor',parameterToMonitor.identifier)"
                    />
                  </b-input-group-text>
                </b-input-group-append>
              </b-input-group>
              <ErrorMessage v-if="questionForm.errors.get('parameters_to_monitor')
                              && JSON.parse(questionForm.errors.get('parameters_to_monitor'))['specific']"
                            :message="JSON.parse(questionForm.errors.get('parameters_to_monitor'))['specific'][parameterToMonitor.identifier]"
              />
            </div>
            <ErrorMessage v-if="questionForm.errors.get('parameters_to_monitor')
                            && JSON.parse(questionForm.errors.get('parameters_to_monitor'))['general']"
                          class="pb-2"
                          :message="JSON.parse(questionForm.errors.get('parameters_to_monitor'))['general']"
            />
            <b-button class="primary" size="sm" @click="addBowTieItem('parametersToMonitor')">
              Add Distractor
            </b-button>
          </b-card-text>
        </b-card>
      </div>
    </b-container>
  </div>
</template>

<script>
import { v4 as uuidv4 } from 'uuid'
import ErrorMessage from '~/components/ErrorMessage'

export default {
  name: 'BowTie',
  components: { ErrorMessage },
  props: {
    qtiJson: {
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
  methods: {
    removeBowTieItem (name, bowTieItems, identifier) {
      if (this.qtiJson[bowTieItems].filter(response => !response.correctResponse).length === 1) {
        this.$noty.info(`You need at least one Distractor for ${name}.`)
        return false
      }
      this.qtiJson[bowTieItems] = this.qtiJson[bowTieItems].filter(bowTieItem => bowTieItem.identifier !== identifier)
    },
    addBowTieItem (bowTieItem) {
      this.qtiJson[bowTieItem].push({ identifier: uuidv4(), value: '', correctResponse: false })
      this.$forceUpdate()
    }
  }
}
</script>

<style scoped>

</style>
