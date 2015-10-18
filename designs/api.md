# 仪器控用户管理

## 接口设计

namespace `YiQiKong/User`


### 注册新用户

#### 调用函数

`YiQiKong/User/Signup(object data)`

#### 参数列表

```
	data    object    具体信息, 结构如下:

	{
		"name":                  // 姓名   必填
		"email":                 // 邮箱   必填
		"phone":                 // 电话   必填
		"password":              // 密码   必填
		"identity":              // 身份证号
		"institution":           // 用户所属机构   必填
	}
```

#### 返回结果

```
    如传递参数错误, 或者操作失败, 则会抛出 exception, exception 错误码、错误信息如下
    {
        1001: "异常参数传入",
    }

    $key (对于web新注册的用户, 也就是非gapper用户返回激活链接中的key)     string        表示用户注册成功
    true (对于已经是gapper用户, 注册成功返回true)    bool    表示注册成功
    false    bool    注册失败
    null    null    对于已经刷入 yiqikong-user 的用户避免重复注册

```


### 更新用户信息

#### 调用函数

`YiQiKong/User/UpdateUser(object data)`

#### 参数列表

```
	data    object    具体信息, 结构如下:

	{
		"email":          // 邮箱
		"name":           // 姓名
		"gender":         // 性别
		"phone":          // 电话
		"identity":       // 身份证号
		"residence":      // 用户居住地
		"institution":    // 用户所属机构
	}
```

#### 返回结果

```
    如果不能根据邮箱获取到用户 则会抛出 exception, exception 错误码、错误信息如下
    {
        1001: "异常参数传入",
    }

    true     bool        表示用户信息更新成功
    false    bool        表示用户信息更新失败

```


### 新注册用户激活

#### 调用函数

`YiQiKong/User/Activation(string key)`

#### 参数列表

```
    key    string    激活链接中的字符串

```

#### 返回结果

```
    如果链接超时或者账户已经激活 则会抛出 exception, exception 错误码、错误信息如下
    {
        1002: "激活链接超时",
        1003: "账户已经激活",
    }

    true     bool        账户激活成功
    false    bool        账户激活失败
```


### 重新生成激活链接中的key值

#### 调用函数

`YiQiKong/User/ReSend(string key)`

#### 参数列表

```
    key    string    激活链接中的字符串

```

#### 返回结果

```
    key    string    新生成的key值
    false    bool    重新生成时发生错误
```


### 用户登陆验证

#### 调用函数

`YiQiKong/User/Verify(string username, string password)`

#### 参数列表

```
    username    string    账户(邮箱),
    password    string    密码,

```

#### 返回结果

```
    true     bool    验证成功
    false    bool    验证失败
```


### 获取用户信息

#### 调用函数

`YiQiKong/User/GetUser($id)`

#### 参数列表
```
    $id 为整型,  则为 gapper_id,
    $id 为字符串, 则为 email 或者为 wechat_openid
```

#### 返回结果
```
    // 用户不存在
    false    bool    获取用户失败

    // 用户存在
    return [
            'id' => $user->id,
            'name' => $user->name,
            'gender' => $user->gender,
            'email' => $user->email,
            'phone' => $user->phone,
            'identity' => $user->identity,
            'residence' => $user->residence,
            'institution' => $user->institution,
            'icon' => $user->icon,
            'gapper_id' => $user->gapper_id,
            'atime' => $user->atime,
            'wechat_bind_status' => $user->wechat_bind_status,
            'wechat_openid' => $user->wechat_openid,
            'lab_id' => $user->lab_id,
            'id_admin' => $user->is_admin,
        ];
```


### 绑定微信

#### 调用函数

`YiQiKong/User/LinkWechat($id, $openId)`

#### 参数列表
```
    $id 整型或者字符串, 若为整型则为gapper_id, 为字符串则为email
    $openId 字符串, wechat_openid
```

#### 返回结果
```
    如果根据$id获取用户失败, 则会抛出 exception, exception 错误码、错误信息如下
    {
        1004: "获取用户失败",
    }

    false    bool    绑定失败
    true     bool    绑定成功
```


### 切换微信用户(一个微信用户切换另外的YiQiKong账户)

#### 调用函数

`YiQiKong/User/SwithWechat($id, $openId)`

#### 参数列表
```
    $id 整型或者字符串, 若为整型则为gapper_id, 为字符串则为email
    $openId 字符串, wechat_openid
```

#### 返回结果
```
    如果根据$id获取用户失败, 则会抛出 exception, exception 错误码、错误信息如下
    {
        1004: "获取用户失败",
    }

    false    bool    切换用户失败
    true     bool    切换用户成功
```

