import './bootstrap';

import Alpine from 'alpinejs';
import { AudioEngine } from './audio/audio-engine';
import { chordsheetPlayer } from './player';

window.AudioEngine = AudioEngine;
window.Alpine = Alpine;

Alpine.data('chordsheetPlayer', chordsheetPlayer);

Alpine.start();
