/**
 * Meting Node.js 基础测试
 */

import Meting from '../src/meting.js';

async function runTests() {
  console.log('=== Meting Node.js 基础测试 ===\n');
  
  const platforms = ['netease', 'tencent', 'kugou', 'baidu', 'kuwo'];
  const testKeyword = '周杰伦';
  
  for (const platform of platforms) {
    console.log(`\n--- 测试平台: ${platform} ---`);
    
    try {
      const meting = new Meting(platform);
      meting.format(true);
      
      // 测试搜索功能
      console.log('测试搜索功能...');
      const searchResult = await meting.search(testKeyword, { limit: 1 });
      const songs = JSON.parse(searchResult);
      
      if (songs.length > 0) {
        const song = songs[0];
        console.log(`✓ 搜索成功: ${song.name} - ${song.artist.join(', ')}`);
        
        // 测试歌曲详情
        console.log('测试获取歌曲详情...');
        const songDetail = await meting.song(song.id);
        const songData = JSON.parse(songDetail);
        if (songData.length > 0) {
          console.log(`✓ 获取歌曲详情成功: ${songData[0].name}`);
        } else {
          console.log('✗ 获取歌曲详情失败');
        }
        
        // 测试获取播放链接
        console.log('测试获取播放链接...');
        try {
          const url = await meting.url(song.url_id, 128);
          const urlData = JSON.parse(url);
          if (urlData.url) {
            console.log('✓ 获取播放链接成功');
          } else {
            console.log('✗ 获取播放链接失败（可能需要会员或已下架）');
          }
        } catch (error) {
          console.log('✗ 获取播放链接出错:', error.message);
        }
        
        // 测试获取歌词
        console.log('测试获取歌词...');
        try {
          const lyric = await meting.lyric(song.lyric_id);
          const lyricData = JSON.parse(lyric);
          if (lyricData.lyric) {
            console.log('✓ 获取歌词成功');
          } else {
            console.log('✗ 获取歌词失败（可能无歌词）');
          }
        } catch (error) {
          console.log('✗ 获取歌词出错:', error.message);
        }
        
        // 测试获取封面
        console.log('测试获取封面图片...');
        try {
          const pic = await meting.pic(song.pic_id, 200);
          const picData = JSON.parse(pic);
          if (picData.url) {
            console.log('✓ 获取封面图片成功');
          } else {
            console.log('✗ 获取封面图片失败');
          }
        } catch (error) {
          console.log('✗ 获取封面图片出错:', error.message);
        }
        
      } else {
        console.log('✗ 搜索失败或无结果');
      }
      
    } catch (error) {
      console.log(`✗ 平台 ${platform} 测试失败:`, error.message);
    }
    
    // 添加延迟避免请求过快
    await new Promise(resolve => setTimeout(resolve, 2000));
  }
  
  console.log('\n=== 测试完成 ===');
}

// 执行测试
runTests().catch(console.error);