/*
 Navicat Premium Data Transfer

 Source Server         : docker.local
 Source Server Type    : MySQL
 Source Server Version : 80017
 Source Host           : docker.local:33060
 Source Schema         : chat

 Target Server Type    : MySQL
 Target Server Version : 80017
 File Encoding         : 65001

 Date: 19/03/2020 15:55:52
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for chat_record
-- ----------------------------
DROP TABLE IF EXISTS `chat_record`;
CREATE TABLE `chat_record`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL DEFAULT 0 COMMENT ''是群聊消息记录的话 此id为0'',
  `group_id` int(11) NOT NULL DEFAULT 0 COMMENT ''如果不为0说明是群聊'',
  `content` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '''',
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 90 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = ''聊天记录'' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for friend
-- ----------------------------
DROP TABLE IF EXISTS `friend`;
CREATE TABLE `friend`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL,
  `friend_group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 86 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = ''好友表'' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for friend_group
-- ----------------------------
DROP TABLE IF EXISTS `friend_group`;
CREATE TABLE `friend_group`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `groupname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 14 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = ''好友分组表'' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of friend_group
-- ----------------------------
INSERT INTO `friend_group` VALUES (1, 10013, ''PHP'');
INSERT INTO `friend_group` VALUES (2, 10014, ''PHP'');
INSERT INTO `friend_group` VALUES (3, 10015, ''前端小组'');
INSERT INTO `friend_group` VALUES (4, 10014, ''JAVA'');

-- ----------------------------
-- Table structure for group
-- ----------------------------
DROP TABLE IF EXISTS `group`;
CREATE TABLE `group`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT ''群组所属用户id,群主'',
  `groupname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT ''群名'',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10016 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = ''群组'' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of group
-- ----------------------------
INSERT INTO `group` VALUES (10000, 10013, ''PHP开发小组'', ''/Static/upload/5d09c956c0ccctimg.jpg'');
INSERT INTO `group` VALUES (10001, 10014, ''C++开发小组'', ''/Static/upload/5d09cdca1dffb3-1G123203S6-50.jpg'');

-- ----------------------------
-- Table structure for group_member
-- ----------------------------
DROP TABLE IF EXISTS `group_member`;
CREATE TABLE `group_member`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 46 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of group_member
-- ----------------------------
INSERT INTO `group_member` VALUES (1, 10001, 10013);
INSERT INTO `group_member` VALUES (2, 10001, 10014);
INSERT INTO `group_member` VALUES (3, 10000, 10013);
INSERT INTO `group_member` VALUES (4, 10000, 10014);
INSERT INTO `group_member` VALUES (5, 10001, 10015);

-- ----------------------------
-- Table structure for offline_message
-- ----------------------------
DROP TABLE IF EXISTS `offline_message`;
CREATE TABLE `offline_message`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `data` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''0未发送 1已发送'',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = ''离线消息表'' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for system_message
-- ----------------------------
DROP TABLE IF EXISTS `system_message`;
CREATE TABLE `system_message`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT ''接收用户id'',
  `from_id` int(11) NOT NULL COMMENT ''来源相关用户id'',
  `group_id` int(11) NOT NULL DEFAULT 0,
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '''' COMMENT ''添加好友附言'',
  `type` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''0好友请求 1请求结果通知'',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''0未处理 1同意 2拒绝'',
  `read` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''0未读 1已读，用来显示消息盒子数量'',
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 88 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = ''系统消息表'' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT ''头像'',
  `nickname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT ''昵称'',
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT ''用户名'',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sign` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT ''签名'',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT ''online'' COMMENT ''online在线 hide隐身 offline离线'',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10016 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = ''用户表'' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of user
-- ----------------------------
INSERT INTO `user` VALUES (10013, ''/Static/upload/5d09c6a5b6def15337177846531748ac16fb.jpg'', ''AAA'', ''test03'', ''$2y$10$cS3rfhqaHeOfVfkAFd1MnuzslrWhihLP/awA07hQPOfUqixV0yp1q'', ''接受失败,但不选择放弃。 '', ''online'');
INSERT INTO `user` VALUES (10014, ''/Static/upload/5d09c7da7bc97tx20218.jpg'', ''BBB'', ''test01'', ''$2y$10$m.4h0u0L56G2Oje6ZnNMyulR.9DMvPT4VkXo2RcTHr8NEqVa.cq8C'', '' 胸无大志,枉活一世。'', ''offline'');
INSERT INTO `user` VALUES (10015, ''/Static/upload/5d09ceac4de6312262La2-0.jpg'', ''CCC'', ''test02'', ''$2y$10$ueMA2hy8x.Tan3nxZlpCmugUcViGCaV/cAeA4V5YX.yU.1kCtAtzq'', ''忘掉失败,不过要牢记失败中的教训。'', ''online'');

SET FOREIGN_KEY_CHECKS = 1;
