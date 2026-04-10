# AI 代码调试系统

一个基于 PHP 的轻量级 AI 代码调试与管理系统，使用 JSON 存储用户、记录和配置数据。适用于小型内部部署或测试环境。

## 主要功能

- 用户注册 / 登录 / 退出
- 邀请码邀请机制（支持无限次复用）
- AI 代码调试提交与记录
- 积分系统：代码分析、每日签到、邀请奖励
- 管理后台：API 配置、邮件配置、系统设置、兑换码管理
- 公告系统：管理员可设置系统公告并在用户控制台展示
- 时区已设置为北京时区（Asia/Shanghai）

## 文件说明

- `config.php`：系统核心配置与数据读写、工具函数、通知、邀请码等逻辑
- `admin.php`：管理员后台页面
- `dashboard.php`：登录用户的控制台页面
- `register.php`：用户注册页面
- `login.php`：用户登录页面
- `invite.php`：邀请码管理页面
- `records.php`：调试记录详情页面
- `redeem.php`：积分兑换码页面
- `data/users.json`：用户数据存储文件
- `data/config.json`：系统配置存储文件
- `data/records.json`：调试记录存储文件

## 安装与使用

1. 将本项目放到支持 PHP 的 Web 服务器目录中（如 Apache、Nginx + PHP）。
2. 确保 `data/` 目录可写，系统会自动创建默认数据文件。
3. 访问 `http://your-server/aicpp/index.php` 进行登录或注册。
4. 管理员账号默认：
   - 用户名：`admin`
   - 密码：`123456`
   推荐在 `config.php` 中第16行修改默认密码为。
5. 进入 `admin.php` 进行系统配置与公告设置。

## 管理配置

- `site_name`：网站名称
- `site_description`：网站描述
- `api_key`、`api_base_url`、`api_model`：AI 服务配置
- `smtp_*`：邮件发送配置
- `announcement_enabled`：公告开关
- `announcement_text`：公告文本
- `invite_reward`：邀请奖励积分

## 注意事项

- 本系统使用文件存储（JSON），适合小规模使用，生产环境请注意权限与安全。
- `config.php` 中已启用 `date_default_timezone_set('Asia/Shanghai')`，系统时间统一为北京时间。
- 默认管理员密码建议首次运行后立即修改。

## 开发者提示

- 如果需要扩展 AI 接口，可在 `config.php` 中修改 `callAIAnalysis()` 或相关 API 配置。
- 兑换码、公告与邀请逻辑均存储在 `data/` 目录下的 JSON 文件中。
- 若出现权限问题，请检查 `data/` 文件夹是否对 PHP 进程可写。
