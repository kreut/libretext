/**
 * Marker questions: keep scoring (correct / incorrect) and feedback attached to
 * the right atoms and bonds after the author edits the molecule.
 *
 * MolView gives atoms and bonds no stable ids, only array positions, and those
 * positions shift as soon as an atom is added or deleted. So atoms are matched
 * by element + 2D position (trying every uniform shift implied by pairing two
 * same-element atoms, in case MolView re-centres the drawing), and bonds by the
 * pair of matched atoms they join. Anything that can't be matched starts with
 * the defaults. Marks always come from the edited structure, i.e. what the
 * author sees in the Sketcher.
 */

const SCORING_KEYS = ['correct', 'incorrect', 'feedback']
// Bookkeeping keys added by MultipleAnswersAdvanced / the backend; stale after an edit.
const STALE_KEYS = ['structuralComponent', 'structuralIndex', 'index']
const POSITION_TOLERANCE = 3

const pos = atom => atom.position || [0, 0]
const bondKey = (a, b) => (a < b ? `${a}-${b}` : `${b}-${a}`)
const hasFeedback = item => !!item && typeof item.feedback === 'string' &&
  item.feedback.replace(/<[^>]*>|&nbsp;|\s/g, '') !== ''

function stripScoring (item) {
  const copy = { ...item }
  for (const key of [...SCORING_KEYS, ...STALE_KEYS]) {
    delete copy[key]
  }
  return copy
}

function carryScoring (target, source, clearFeedback) {
  for (const key of SCORING_KEYS) {
    if (key in source) {
      target[key] = source[key]
    }
  }
  if (clearFeedback) {
    delete target.feedback
  }
}

function matchAtoms (oldAtoms, newAtoms, dx, dy) {
  const newToOld = new Array(newAtoms.length).fill(null)
  const usedOld = new Set()
  newAtoms.forEach((atom, n) => {
    let bestOld = null
    let bestDist = Infinity
    oldAtoms.forEach((old, o) => {
      if (usedOld.has(o) || old.symbol !== atom.symbol) return
      const dist = Math.hypot(pos(atom)[0] - dx - pos(old)[0], pos(atom)[1] - dy - pos(old)[1])
      if (dist <= POSITION_TOLERANCE && dist < bestDist) {
        bestOld = o
        bestDist = dist
      }
    })
    if (bestOld !== null) {
      newToOld[n] = bestOld
      usedOld.add(bestOld)
    }
  })
  return newToOld
}

function bestAtomMatch (oldAtoms, newAtoms) {
  const shifts = [[0, 0]]
  const seen = new Set(['0,0'])
  for (const o of oldAtoms) {
    for (const n of newAtoms) {
      if (o.symbol !== n.symbol) continue
      const dx = pos(n)[0] - pos(o)[0]
      const dy = pos(n)[1] - pos(o)[1]
      const key = `${Math.round(dx)},${Math.round(dy)}`
      if (!seen.has(key)) {
        seen.add(key)
        shifts.push([dx, dy])
      }
    }
  }
  const maxPossible = Math.min(oldAtoms.length, newAtoms.length)
  let best = new Array(newAtoms.length).fill(null)
  let bestCount = -1
  for (const [dx, dy] of shifts) {
    const map = matchAtoms(oldAtoms, newAtoms, dx, dy)
    const count = map.filter(o => o !== null).length
    if (count > bestCount) {
      best = map
      bestCount = count
    }
    if (count === maxPossible) break
  }
  return best
}

function matchStructures (before, after) {
  const oldAtoms = (before && before.atoms) || []
  const oldBonds = (before && before.bonds) || []
  const newAtoms = (after && after.atoms) || []
  const newBonds = (after && after.bonds) || []
  const newToOld = bestAtomMatch(oldAtoms, newAtoms)
  const oldBondIndexByKey = new Map(oldBonds.map((bond, i) => [bondKey(bond.atoms[0], bond.atoms[1]), i]))
  const bondNewToOld = newBonds.map(bond => {
    const a = newToOld[bond.atoms[0]] ?? null
    const b = newToOld[bond.atoms[1]] ?? null
    const i = a !== null && b !== null ? oldBondIndexByKey.get(bondKey(a, b)) : undefined
    return i === undefined ? null : i
  })
  return { oldAtoms, oldBonds, newAtoms, newBonds, newToOld, bondNewToOld }
}

// True if the author added, removed or changed any atom or bond (moving the
// whole drawing or changing marks doesn't count).
export function moleculeChanged (before, after) {
  const m = matchStructures(before, after)
  if (m.oldAtoms.length !== m.newAtoms.length || m.oldBonds.length !== m.newBonds.length) return true
  if (m.newToOld.some(o => o === null) || m.bondNewToOld.some(o => o === null)) return true
  if (m.newAtoms.some((atom, n) => (atom.charge ?? null) !== (m.oldAtoms[m.newToOld[n]].charge ?? null))) return true
  return m.newBonds.some((bond, n) => bond.type !== m.oldBonds[m.bondNewToOld[n]].type)
}

export function structureHasFeedback (structure) {
  return ((structure && structure.atoms) || []).some(hasFeedback) ||
    ((structure && structure.bonds) || []).some(hasFeedback)
}

// Builds the edited structure with scoring and feedback carried over from
// the matching atoms/bonds of the original. With clearFeedback, scoring is
// still carried over but every piece of feedback is removed.
export function carryOverScoring (before, after, { clearFeedback = false } = {}) {
  const m = matchStructures(before, after)
  const atoms = m.newAtoms.map((atom, n) => {
    const copy = stripScoring(atom)
    if (m.newToOld[n] !== null) carryScoring(copy, m.oldAtoms[m.newToOld[n]], clearFeedback)
    return copy
  })
  const bonds = m.newBonds.map((bond, n) => {
    const copy = stripScoring(bond)
    if (m.bondNewToOld[n] !== null) carryScoring(copy, m.oldBonds[m.bondNewToOld[n]], clearFeedback)
    return copy
  })
  return { ...after, atoms, bonds }
}
