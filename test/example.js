/**
 * Meting Node.js 使用示例
 */

import Meting from '../lib/meting.esm.js';

async function main() {
  // 创建 Meting 实例
  const meting = new Meting('netease'); // 可选: 'netease', 'tencent', 'kugou', 'baidu', 'kuwo'
  
  // 开启数据格式化
  meting.format(true);
  
  console.log('=== Meting Node.js 示例 ===\n');
  
  try {
    // 1. 搜索歌曲
    console.log('1. 搜索歌曲：');
    const searchResult = await meting.search('烟火里的尘埃', { limit: 3 });
    console.log('搜索结果：');
    console.log(JSON.stringify(JSON.parse(searchResult), null, 2));
    console.log('\n');
    
    // 获取第一首歌的 ID
    const songs = JSON.parse(searchResult);
    if (songs.length > 0) {
      const firstSong = songs[0];
      console.log(`选择歌曲: ${firstSong.name} - ${firstSong.artist.join(', ')}\n`);
      
      // 2. 获取歌曲详情
      console.log('2. 获取歌曲详情：');
      const songDetail = await meting.song(firstSong.id);
      console.log('歌曲详情：');
      console.log(JSON.stringify(JSON.parse(songDetail), null, 2));
      console.log('\n');
      
      // 3. 获取歌曲播放链接
      console.log('3. 获取歌曲播放链接：');
      const url = await meting.url(firstSong.url_id, 320);
      console.log('播放链接：');
      console.log(JSON.stringify(JSON.parse(url), null, 2));
      console.log('\n');
      
      // 4. 获取歌词
      console.log('4. 获取歌词：');
      const lyric = await meting.lyric(firstSong.lyric_id);
      const lyricData = JSON.parse(lyric);
      console.log('歌词预览（前5行）：');
      if (lyricData.lyric) {
        const lines = lyricData.lyric.split('\n').slice(0, 5);
        lines.forEach(line => {
          if (line.trim()) console.log(line);
        });
      } else {
        console.log('暂无歌词');
      }
      console.log('\n');
      
      // 5. 获取封面图片
      console.log('5. 获取封面图片：');
      const pic = await meting.pic(firstSong.pic_id, 300);
      console.log('封面图片：');
      console.log(JSON.stringify(JSON.parse(pic), null, 2));
      console.log('\n');
    }
    
    // 6. 切换到其他平台测试
    console.log('6. 切换到腾讯音乐平台：');
    meting.site('tencent');
    const tencentSearch = await meting.search('邓紫棋', { limit: 2 });
    console.log('腾讯音乐搜索结果：');
    console.log(JSON.stringify(JSON.parse(tencentSearch), null, 2));
    console.log('\n');
    
  } catch (error) {
    console.error('发生错误：', error);
  }
}

// 运行示例
main().then(() => {
  console.log('示例运行完成！');
}).catch(error => {
  console.error('示例运行失败：', error);
});