# voicerec
本モジュールは、Moodle上で録音問題を作成できるMoodleの「活動モジュール」です。
語学用のコースで使っていただくことを想定しています。

Moodle用の録音用ツールとしては、NanoGong というMoodleプラグインがありましたが、Javaアプレットというブラウザプラグインを使用しています。  
ChromeはすでにNPAPI (Javaアプレットに必要な技術)をサポートしなくなりました。Firefoxも年内のサポート打ち切りを宣言しています。  
Adobe Flashも含めて、Java アプレットなどのブラウザプラグインを使用したプログラムが動作する環境はここ数年のうちになくなるものと考えられます。  
そのため、NanoGongの置き換えとして、Moodleプラグインの雛形newmoduleにNanoGongのコードを参考にしながら必要最低限の機能を実装しました。  
録音にはJavaScriptの機能だけを使用しており、ブラウザプラグインは使用していません。今後は、Web上での録音はこのような形になっていくはずなのですが、まだ全てのブラウザでその手段が実装されているわけではありません。  
現状では、動作する環境を非常に選ぶモジュールとなっていることをご了承ください。  

##動作条件

Moodle2.7以降で動作します。  
PCではChrome、Firefox、Operaで確認しています。Edge、IE、Safariでは動作しません。  
スマホ、タブレットでは、 Android上のChromeなどでも動作しますが、現在のところiOS上のブラウザは全て動作しません。  
録音出来る、出来ないはブラウザ側のAPIの実装によって状況がかわりますので、最新の状況は、http://caniuse.com/（ブラウザの実装状況サイト）でMediaRecorderをご覧ください。  
また、ChromeやOperaはサーバがHTTPSでないと動作しなくなりました。サーバがHTTPの場合は、Firefoxでお試しください。  

##インストール方法
Moodleのmod/下にvoicerecの名前で配置してください。  

##注意事項 Warning
本ソフトウェアに起因するいかなる問題についても私は一切の責任を負いません。予めご了承ください。 本ソフトウェアのライセンスはMoodle上のライセンスに従います。  
Moodle is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by　the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Moodle is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details. 
You should have received a copy of the GNU General Public License along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


##Eラーニングに基づく英語とフランス語の学習行動の可視化の試み
科学研究費助成事業(基盤研究B)26284076『Eラーニングに基づく英語とフランス語の学習行動の可視化の試み』 （研究代表者 吉冨 朝子（東京外国語大学）， 研究分担者 井之川 睦美（東京外国語大学），鈴木 陽子（東京外国語大学），斎藤 弘子（東京外国語大学）， 浦田 和幸（東京外国語大学），川口 裕司（東京外国語大学），梅野 毅（東京外国語大学））による研究の一環として公開しています。


