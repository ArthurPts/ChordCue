import './bootstrap';

import Alpine from 'alpinejs';
import { AudioEngine } from './audio/audio-engine';
import { chordsheetPlayer } from './alpine/player';
import { simpleEditor } from './alpine/simple-editor';
import { beatEditor } from './alpine/beat-editor';

window.AudioEngine = AudioEngine;
window.Alpine = Alpine;

Alpine.data('chordsheetPlayer', chordsheetPlayer);
Alpine.data('simpleEditor', simpleEditor);
Alpine.data('beatEditor', beatEditor);

Alpine.start();
